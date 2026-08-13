# Person Affected API

Use this endpoint to send an affected-person event from the external system.

## Connection details

- Method: `POST`
- URL: `https://YOUR-DOMAIN/api/person-affecteds`
- Headers:
  - `Authorization: Bearer YOUR_SHARED_TOKEN`
  - `Idempotency-Key: SOURCE_EVENT_UNIQUE_ID`
  - `X-Request-ID: UNIQUE_REQUEST_OR_CORRELATION_ID` (optional; server-generated if omitted)
  - `Accept: application/json`
  - `Content-Type: application/json`

The receiving system administrator must generate a strong shared token, set it as
`SYSTEM_A_API_TOKEN` in the receiving application's `.env`, and provide the same token to the
sending system through a secure channel. Run `php artisan config:clear` after changing it.

## Required JSON fields

| Field | Type | Rules |
| --- | --- | --- |
| `control_number` | string | Required; maximum 255 characters |
| `status` | string | Required; must be `affected` |
| `date_tagged` | string | Required ISO-8601 timestamp including seconds and an explicit `Z` or numeric timezone offset |

Example minimal request:

```json
{
  "control_number": "CN-10001",
  "status": "affected",
  "date_tagged": "2026-08-03T14:35:26+08:00"
}
```

Example cURL request:

```bash
curl -X POST "https://YOUR-DOMAIN/api/person-affecteds" \
  -H "Authorization: Bearer YOUR_SHARED_TOKEN" \
  -H "Idempotency-Key: tciss-event-2026-000001" \
  -H "X-Request-ID: tciss-request-2026-000001" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"control_number":"CN-10001","status":"affected","date_tagged":"2026-08-03T14:35:26+08:00"}'
```

## Duplicate behavior

The server converts `date_tagged` to UTC before saving and checking its calendar date.

- A new control number creates one row in `person_affecteds` and one row in
  `person_affected_statuses`.
- An existing control number is not inserted again. A status is added only when no status for
  that person exists on the same UTC calendar date.
- A retry for the same person and UTC date changes nothing and returns the original status event.

## Idempotency and safe retries

Every request must include an `Idempotency-Key` header containing the sending system's permanent,
unique identifier for that source event. It must be 8-255 characters and may contain letters,
numbers, `.`, `_`, `:`, and `-`. Do not generate a new key when retrying the same source event.

The server scopes keys to the authenticated client and atomically stores the key, SHA-256 request
body hash, HTTP status, and JSON response with the affected-person changes.

- Identical key and identical raw JSON body: the original status and JSON response are returned,
  with `Idempotency-Replayed: true`; no person, profile, family, or status data is changed.
- Identical key with a different raw JSON body: HTTP `409 Conflict`; no data is changed.
- Different key for an already-recorded person/date: the existing date event is returned and the
  profile and family are not overwritten.
- Missing or malformed key: HTTP `422 Unprocessable Entity`.

The sender must serialize an identical JSON body when retrying. Changing whitespace, field order,
or values changes the request-body hash and is treated as different content.

## Request correlation and audit logging

The sender may provide `X-Request-ID` using 8-128 letters, numbers, `.`, `_`, `:`, or `-`. The
server returns the accepted or generated ID in the `X-Request-ID` response header. Use it when
reporting an integration problem.

Every API attempt is written to the dedicated `api_audit_logs` table, including rejected,
rate-limited, conflicting, replayed, and successful requests. Records contain only client ID,
request ID, source event reference, control number, source IP, method/route, response status,
processing time, outcome, and timestamp. Bearer tokens, authorization headers, complete request
bodies, and resident profile/family data are never stored in this audit table.

## Responses

New status event: HTTP `201 Created` with `person_created: true|false` and
`event_created: true`.

Duplicate same-day event: HTTP `200 OK` with `person_created: false` and
`event_created: false`.

```json
{
  "success": true,
  "message": "Affected event already recorded.",
  "data": {
    "person_affected_id": 10,
    "person_affected_status_id": 21,
    "control_number": "CN-10001",
    "status": "affected",
    "date_tagged": "2026-08-03T06:35:26.000000+00:00",
    "person_created": false,
    "event_created": false
  }
}
```

Other results:

- `401 Unauthorized`: missing/incorrect bearer token.
- `409 Conflict`: an idempotency key was reused with different request content.
- `413 Content Too Large`: request exceeds the configured body limit.
- `415 Unsupported Media Type`: content type is not `application/json`.
- `422 Unprocessable Entity`: invalid JSON fields. The response contains an `errors` object keyed
  by field name.

The sender should treat both `200` and `201` as successful delivery. It may safely retry a request
after a timeout because same-day requests are idempotent.

## Rate limiting

The endpoint allows 60 requests per minute per authenticated client credential, with a maximum
burst of 10 requests per second. A request over either limit receives HTTP `429 Too Many Requests`.
The sender must honor the `Retry-After` response header before retrying.

Administrators can tune the defaults without changing code:

```env
SYSTEM_A_RATE_LIMIT_PER_MINUTE=60
SYSTEM_A_RATE_LIMIT_BURST_PER_SECOND=10
```

Run `php artisan config:clear` after changing these settings. The current bearer-token integration
uses a SHA-256 credential fingerprint as its client identity; the raw token is never included in
the rate-limit cache key. Once database-backed API clients are enabled, their authenticated client
ID can be supplied to the same limiter. IP address is used only when no authenticated client ID is
available.

## Strict request validation

- `Content-Type` must be `application/json` (an optional charset parameter is accepted).
- The default maximum request body is 262,144 bytes (256 KiB).
- `date_tagged` must include `Z` or a numeric UTC offset and cannot be more than five minutes in
  the future. `SYSTEM_A_CLOCK_SKEW_SECONDS` controls this allowance.
- `birthdate`, when supplied, cannot be later than the current UTC date.
- `monthly_income`, when supplied, is a string (maximum 255 characters) and is stored exactly as sent by TCISS.
- Person, family-head, and family-member control numbers are trimmed, converted to uppercase, and
  have whitespace removed before validation and database lookup. For example, ` cn- 10001 ` is
  stored as `CN-10001`.
- Fields not listed in the documented schema are rejected, including unknown nested family-member
  fields. Coordinate schema additions before sending new fields.

Laravel limits are configurable as follows:

```env
SYSTEM_A_MAX_BODY_BYTES=262144
SYSTEM_A_CLOCK_SKEW_SECONDS=300
```

The reverse proxy must enforce the same limit before PHP. Deployment-ready examples are provided
in `deployment/apache-person-affected-api.conf.example` and
`deployment/nginx-person-affected-api.conf.example`. Install the relevant snippet in the HTTPS
virtual host/server block and reload the web server; keeping the example file in the repository
does not activate the server-level limit by itself.
