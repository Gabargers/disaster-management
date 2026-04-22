<?php

namespace App\Services\Crud;

use App\Services\Log\LogServices;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CrudServices
{
    public function __construct(
        private readonly LogServices $logServices
    ) {}

    public function store(string|Model $model, array $payload, array $options = []): Model
    {
        $modelInstance = $this->resolveModel($model);

        try {
            $created = DB::transaction(function () use ($modelInstance, $payload, $options) {
                $data = $this->sanitizePayload($modelInstance, $payload, $options);

                $record = $modelInstance::query()->create($data);

                $this->syncRelations($record, $payload, $options);
                $this->handleFiles($record, $payload, $options);

                return $record->refresh();
            });

            $this->logSuccessIfEnabled($created, 'created', $payload, $options);

            return $created;
        } catch (Throwable $e) {
            $this->logErrorIfEnabled($modelInstance, 'create_failed', $payload, $options, $e);
            throw $e;
        }
    }

    public function update(Model $record, array $payload, array $options = []): Model
    {
        try {
            $updated = DB::transaction(function () use ($record, $payload, $options) {
                $data = $this->sanitizePayload($record, $payload, $options);

                $record->fill($data)->save();

                $this->syncRelations($record, $payload, $options);
                $this->handleFiles($record, $payload, $options);

                return $record->refresh();
            });

            $this->logSuccessIfEnabled($updated, 'updated', $payload, $options);

            return $updated;
        } catch (Throwable $e) {
            $this->logErrorIfEnabled($record, 'update_failed', $payload, $options, $e);
            throw $e;
        }
    }

    public function delete(Model $record, array $options = []): bool
    {
        try {
            $deleted = DB::transaction(function () use ($record, $options) {
                if (!empty($options['delete_files']) && method_exists($record, 'documents')) {
                    $disk = $options['file_disk'] ?? 'public';

                    foreach ($record->documents as $document) {
                        if (!empty($document->file_path)) {
                            Storage::disk($disk)->delete($document->file_path);
                        }

                        $document->delete();
                    }
                }

                if (isset($options['before']) && is_callable($options['before'])) {
                    ($options['before'])($record);
                }

                return (bool) $record->delete();
            });

            $this->logSuccessIfEnabled($record, 'deleted', [], $options);

            return $deleted;
        } catch (Throwable $e) {
            $this->logErrorIfEnabled($record, 'delete_failed', [], $options, $e);
            throw $e;
        }
    }

    private function resolveModel(string|Model $model): Model
    {
        return $model instanceof Model ? $model : new $model;
    }

    private function sanitizePayload(Model $model, array $payload, array $options): array
    {
        if (!empty($options['only'])) {
            $data = Arr::only($payload, $options['only']);
        } else {
            $fillable = $model->getFillable();

            if (empty($fillable)) {
                throw new RuntimeException(
                    'SECURITY: Model has no $fillable defined. Define $fillable or pass options[only].'
                );
            }

            $data = Arr::only($payload, $fillable);
        }

        if (!empty($options['except'])) {
            $data = Arr::except($data, $options['except']);
        }

        return $data;
    }

    private function syncRelations(Model $record, array $payload, array $options): void
    {
        $sync = $options['sync'] ?? [];

        if (empty($sync)) {
            return;
        }

        foreach ($sync as $relation => $method) {
            if (!array_key_exists($relation, $payload)) {
                continue;
            }

            if (!method_exists($record, $relation)) {
                continue;
            }

            $values = $payload[$relation];

            if (method_exists($record->$relation(), $method)) {
                $record->$relation()->{$method}($values);
            }
        }
    }

    private function handleFiles(Model $record, array $payload, array $options): void
    {
        $files = $options['files'] ?? [];

        if (empty($files)) {
            return;
        }

        if (!method_exists($record, 'documents')) {
            throw new RuntimeException('Model must define a documents() relation to use dynamic file uploads.');
        }

        foreach ($files as $field => $config) {
            if (!array_key_exists($field, $payload) || empty($payload[$field])) {
                continue;
            }

            $disk = $config['disk'] ?? 'public';
            $remarks = $config['remarks'] ?? $field;
            $multiple = $config['multiple'] ?? false;
            $replace = $config['replace'] ?? true;

            $uploaded = $payload[$field];

            if ($multiple) {
                if (!is_array($uploaded)) {
                    continue;
                }

                if ($replace) {
                    $this->deleteDocumentsByRemarks($record, $remarks, $disk);
                }

                foreach ($uploaded as $file) {
                    if ($file instanceof UploadedFile) {
                        $this->storeSingleFile($record, $file, $field, $config, $disk, $remarks);
                    }
                }
            } else {
                if (!($uploaded instanceof UploadedFile)) {
                    continue;
                }

                if ($replace) {
                    $this->deleteDocumentsByRemarks($record, $remarks, $disk);
                }

                $this->storeSingleFile($record, $uploaded, $field, $config, $disk, $remarks);
            }
        }
    }

    private function storeSingleFile(
        Model $record,
        UploadedFile $file,
        string $field,
        array $config,
        string $disk,
        string $remarks
    ): void {
        $directory = $this->resolveDirectory($record, $file, $field, $config);
        $filename = $this->resolveFilename($record, $file, $field, $config, $remarks);

        $path = $file->storeAs($directory, $filename, $disk);

        $record->documents()->create([
            'file_path' => $path,
            'file_name' => $filename,
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'remarks'   => $remarks,
        ]);
    }

    private function resolveDirectory(
        Model $record,
        UploadedFile $file,
        string $field,
        array $config
    ): string {
        $directory = $config['directory'] ?? 'uploads';

        if (is_callable($directory)) {
            $directory = $directory($record, $file, $field, $config);
        }

        return trim((string) $directory, '/');
    }

    private function resolveFilename(
        Model $record,
        UploadedFile $file,
        string $field,
        array $config,
        string $remarks
    ): string {
        $filename = $config['filename'] ?? null;

        if (is_callable($filename)) {
            return (string) $filename($record, $file, $field, $config);
        }

        if (is_string($filename) && $filename !== '') {
            return $filename;
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $uuidField = $config['uuid_field'] ?? $this->detectUuidField($record);

        if ($uuidField && !empty($record->{$uuidField})) {
            return $record->{$uuidField} . '_' . $remarks . '.' . $extension;
        }

        return uniqid() . '_' . $remarks . '.' . $extension;
    }

    private function detectUuidField(Model $record): ?string
    {
        $candidates = [
            'pet_uuid',
            'uuid',
        ];

        foreach ($candidates as $field) {
            if (isset($record->{$field}) && !empty($record->{$field})) {
                return $field;
            }
        }

        return null;
    }

    private function deleteDocumentsByRemarks(Model $record, string $remarks, string $disk): void
    {
        $documents = $record->documents()->where('remarks', $remarks)->get();

        foreach ($documents as $document) {
            if (!empty($document->file_path)) {
                Storage::disk($disk)->delete($document->file_path);
            }

            $document->delete();
        }
    }

    private function safePayloadForLogs(array $payload, array $options): array
    {
        $defaultHidden = [
            'password',
            'password_confirmation',
            'otp',
            'token',
            'access_token',
            'refresh_token',
            'remember_token',
            'secret',
            'api_key',
        ];

        $hidden = array_values(array_unique(array_merge($defaultHidden, $options['log_hidden'] ?? [])));
        $allowed = $options['log_payload_keys'] ?? null;

        $safe = is_array($allowed)
            ? Arr::only($payload, $allowed)
            : [];

        return Arr::except($safe, $hidden);
    }

    private function logSuccessIfEnabled(Model $model, string $defaultEvent, array $payload, array $options): void
    {
        if (($options['log'] ?? true) !== true) {
            return;
        }

        $event = $options['event'] ?? $defaultEvent;
        $logName = $options['log_name'] ?? null;
        $attributeKeys = $options['log_attribute_keys'] ?? ['id'];

        $properties = [
            'attribute_keys' => $attributeKeys,
        ];

        $safePayload = $this->safePayloadForLogs($payload, $options);

        if (!empty($safePayload)) {
            $properties['payload'] = $safePayload;
        }

        $this->logServices->logSuccess($model, $event, $logName, $properties);
    }

    private function logErrorIfEnabled(Model|string $model, string $defaultEvent, array $payload, array $options, Throwable $e): void
    {
        if (($options['log'] ?? true) !== true) {
            return;
        }

        $event = $options['event_error'] ?? $defaultEvent;
        $logName = $options['log_name'] ?? null;

        $properties = [
            'message' => $e->getMessage(),
        ];

        $safePayload = $this->safePayloadForLogs($payload, $options);

        if (!empty($safePayload)) {
            $properties['payload'] = $safePayload;
        }

        $this->logServices->logError($model, $event, $logName, $properties);
    }
}