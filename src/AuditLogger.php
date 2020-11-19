<?php

namespace Connf\Auditlog;

use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Model;
use Connf\Auditlog\Models\Audit;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Config\Repository;
use Connf\Auditlog\Exceptions\CouldNotLogAudit;

class AuditLogger
{
    use Macroable;

    /** @var \Illuminate\Auth\AuthManager */
    protected $auth;

    protected $logName = '';

    /** @var bool */
    protected $logEnabled;

    /** @var \Illuminate\Database\Eloquent\Model */
    protected $performedOn;

    /** @var \Illuminate\Database\Eloquent\Model */
    protected $causedBy;

    /** @var \Illuminate\Support\Collection */
    protected $properties;

    /** @var string */
    protected $authDriver;

    public function __construct(AuthManager $auth, Repository $config)
    {
        $this->auth = $auth;

        $this->properties = collect();

        $this->authDriver = $config['auditlog']['default_auth_driver'] ?? $auth->getDefaultDriver();

        if (starts_with(app()->version(), '5.1')) {
            $this->causedBy = $auth->driver($this->authDriver)->user();
        } else {
            $this->causedBy = $auth->guard($this->authDriver)->user();
        }

        $this->logName = $config['auditlog']['default_log_name'];

        $this->logEnabled = $config['auditlog']['enabled'] ?? true;
    }

    public function performedOn(Model $model)
    {
        $this->performedOn = $model;

        return $this;
    }

    public function on(Model $model)
    {
        return $this->performedOn($model);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|int|string $modelOrId
     *
     * @return $this
     */
    public function causedBy($modelOrId)
    {
        $model = $this->normalizeCauser($modelOrId);

        $this->causedBy = $model;

        return $this;
    }

    public function by($modelOrId)
    {
        return $this->causedBy($modelOrId);
    }

    /**
     * @param array|\Illuminate\Support\Collection $properties
     *
     * @return $this
     */
    public function withProperties($properties)
    {
        $this->properties = collect($properties);

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function withProperty(string $key, $value)
    {
        $this->properties->put($key, $value);

        return $this;
    }

    public function useLog(string $logName)
    {
        $this->logName = $logName;

        return $this;
    }

    public function inLog(string $logName)
    {
        return $this->useLog($logName);
    }

    /**
     * @param string $description
     *
     * @return null|mixed
     */
    public function log(string $description)
    {
        if (! $this->logEnabled) {
            return;
        }

        $Audit = AuditlogServiceProvider::getAuditModelInstance();

        if ($this->performedOn) {
            $Audit->subject()->associate($this->performedOn);
        }

        if ($this->causedBy) {
            $Audit->causer()->associate($this->causedBy);
        }

        $Audit->properties = $this->properties;

        $Audit->description = $this->replacePlaceholders($description, $Audit);

        $Audit->log_name = $this->logName;

        $Audit->save();

        return $Audit;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|int|string $modelOrId
     *
     * @throws \Spatie\Auditlog\Exceptions\CouldNotLogAudit
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function normalizeCauser($modelOrId): Model
    {
        if ($modelOrId instanceof Model) {
            return $modelOrId;
        }

        if (starts_with(app()->version(), '5.1')) {
            $model = $this->auth->driver($this->authDriver)->getProvider()->retrieveById($modelOrId);
        } else {
            $model = $this->auth->guard($this->authDriver)->getProvider()->retrieveById($modelOrId);
        }

        if ($model) {
            return $model;
        }

        throw CouldNotLogAudit::couldNotDetermineUser($modelOrId);
    }

    protected function replacePlaceholders(string $description, Audit $Audit): string
    {
        return preg_replace_callback('/:[a-z0-9._-]+/i', function ($match) use ($Audit) {
            $match = $match[0];

            $attribute = (string) string($match)->between(':', '.');

            if (! in_array($attribute, ['subject', 'causer', 'properties'])) {
                return $match;
            }

            $propertyName = substr($match, strpos($match, '.') + 1);

            $attributeValue = $Audit->$attribute;

            if (is_null($attributeValue)) {
                return $match;
            }

            $attributeValue = $attributeValue->toArray();

            return array_get($attributeValue, $propertyName, $match);
        }, $description);
    }
}
