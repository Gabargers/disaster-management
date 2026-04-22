<?php

namespace App\Services\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class LogServices
{
    public function logSuccess(
        Model|string|null $model,
        string $event,
        ?string $logName = null,
        array $properties = []
    ): void {
        try {
            
            if ($model instanceof Model) {
                $modelClass = class_basename($model);
            } elseif (is_string($model)) {
                $modelClass = class_basename($model);
            } else {
                $modelClass = 'UnknownModel';
            }
            
            $logName = $logName ?? Str::snake($modelClass);

            $logger = activity($logName);

            if (Auth::check()) {
                $logger->causedBy(Auth::user());
            }
            
            if ($model instanceof Model) {
                $logger->performedOn($model);
            }
            
            $logger->event($event)
                ->withProperties(array_merge([
                    'ip'         => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'attributes' => $model instanceof Model ? $model->getAttributes() : null,
                ], $properties))
                ->log("{$modelClass} {$event}.");

        } catch (Throwable $e) {

            Log::error('Failed to write success activity log', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function logError(
        Model|string|null $model,
        string $event,
        ?string $logName = null,
        array $properties = []
    ): void {
        try {

            if ($model instanceof Model) {
                $modelClass = class_basename($model);
            } elseif (is_string($model)) {
                $modelClass = class_basename($model);
            } else {
                $modelClass = 'UnknownModel';
            }

            $logName = $logName ?? Str::snake($modelClass . '_error');

            $logger = activity($logName);

            if (Auth::check()) {
                $logger->causedBy(Auth::user());
            }

            if ($model instanceof Model) {
                $logger->performedOn($model);
            }

            $logger->event($event)
                ->withProperties($properties)
                ->log("{$modelClass} {$event}.");

        } catch (Throwable $e) {
            Log::error('Failed to write error activity log', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
