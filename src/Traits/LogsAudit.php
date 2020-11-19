<?php

namespace Connf\Auditlog\Traits;

use Illuminate\Support\Collection;
use Connf\Auditlog\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Connf\Auditlog\AuditlogServiceProvider;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait LogsAudit
{
    use DetectsChanges;

    protected $enableLoggingModelsEvents = true;

    protected static function bootLogsAudit()
    {
        static::eventsToBeRecorded()->each(function ($eventName) {
            return static::$eventName(function (Model $model) use ($eventName) {
                if (! $model->shouldLogEvent($eventName)) {
                    return;
                }

                $description = $model->getDescriptionForEvent($eventName);

                $logName = $model->getLogNameToUse($eventName);

                if ($description == '') {
                    return;
                }

                if ($properties = $model->attributeValuesToBeLogged($eventName)) {
                    app(AuditLogger::class)
                        ->useLog($logName)
                        ->performedOn($model)
                        ->withProperties($properties)
                        ->log($description);
                }
            });
        });
    }

    public function disableLogging()
    {
        $this->enableLoggingModelsEvents = false;

        return $this;
    }

    public function enableLogging()
    {
        $this->enableLoggingModelsEvents = true;

        return $this;
    }

    public function Audit(): MorphMany
    {
        return $this->morphMany(AuditlogServiceProvider::determineAuditModel(), 'subject');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return $eventName;
    }

    public function getLogNameToUse(string $eventName = ''): string
    {
        return config('auditlog.default_log_name');
    }

    /*
     * Get the event names that should be recorded.
     */
    protected static function eventsToBeRecorded(): Collection
    {
        if (isset(static::$recordEvents)) {
            return collect(static::$recordEvents);
        }

        $events = collect([
            'created',
            'updated',
            'deleted',
        ]);

        if (collect(class_uses_recursive(__CLASS__))->contains(SoftDeletes::class)) {
            $events->push('restored');
        }

        return $events;
    }

    public function attributesToBeIgnored(): array
    {
        if (! isset(static::$ignoreChangedAttributes)) {
            return ['password', 'created_at', 'updated_at'];
        }

        return static::$ignoreChangedAttributes;
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        if (! $this->enableLoggingModelsEvents) {
            return false;
        }

        if (! in_array($eventName, ['created', 'updated', 'deleted'])) {
            return true;
        }

        if (array_has($this->getDirty(), 'deleted_at')) {
            if ($this->getDirty()['deleted_at'] === null) {
                return false;
            }
        }

        //do not log update event if only ignored attributes are changed
        return (bool) count(array_except($this->getDirty(), $this->attributesToBeIgnored()));
    }
}
