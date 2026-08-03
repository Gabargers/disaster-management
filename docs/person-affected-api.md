# Person Affected API

Use this endpoint to send an affected-person event from the external system.

## Connection details

- Method: `POST`
- URL: `https://YOUR-DOMAIN/api/person-affecteds`
- Headers:
  - `Authorization: Bearer YOUR_SHARED_TOKEN`
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
| `date_tagged` | string | Required ISO-8601 timestamp, including seconds; timezone recommended |

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
- `422 Unprocessable Entity`: invalid JSON fields. The response contains an `errors` object keyed
  by field name.

The sender should treat both `200` and `201` as successful delivery. It may safely retry a request
after a timeout because same-day requests are idempotent.
