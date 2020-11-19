<?php

namespace Connf\Auditlog\Traits;

use Illuminate\Database\Eloquent\Model;
use Connf\Auditlog\Exceptions\CouldNotLogChanges;

trait DetectsChanges
{
    protected $oldAttributes = []; // Placeholder

    protected $ignoreAttributes = [
        'created_at',
        'updated_at'
    ]; // Ignore changes to these fields

    protected $scrubAttributes = [
        'password',
    ]; // Scrub these fields values when logging changes

    protected static function bootDetectsChanges()
    {
        if (static::eventsToBeRecorded()->contains('updated')) {
            static::updating(function (Model $model) {

                //temporary hold the original attributes on the model
                //as we'll need these in the updating event
                $oldValues = $model->replicate()->setRawAttributes($model->getOriginal());

                $model->oldAttributes = static::logChanges($oldValues);
            });
        }
    }

    public function attributesToBeLogged(): array
    {
        // Changed Default to Log ALL except for specified or global ...

        // ... instead of Log ONLY specified
        // if (! isset(static::$logAttributes)) {
        //     return [];
        // }

        // return static::$logAttributes;

        // Remove global attributes
        $loggable = array_except($this->attributes, $this->ignoreAttributes);

        // Remove local attributes (Defined in
        // `Classname {
        //      protected $ignoreAttributes = ['column'];
        // }`)
        if (isset(static::$ignoreAttributes) && is_array(static::$ignoreAttributes)) {
            $loggable = array_except($loggable, static::$ignoreAttributes);
        }

        // Return keys only
        return array_keys($loggable);
    }

    public function shouldlogOnlyDirty(): bool
    {
        if (! isset(static::$logOnlyDirty)) {
            return true; // Changed Default to true - Override to false if required
        }

        return static::$logOnlyDirty;
    }

    public function attributeValuesToBeLogged(string $processingEvent): array
    {
        if (! count($this->attributesToBeLogged())) {
            return [];
        }

        $properties['attributes'] = static::logChanges($this->exists ? $this->fresh() : $this);

        foreach ($this->ignoreAttributes as $ignore) {
            unset($properties['attributes'][$ignore]);
        }

        foreach ($this->scrubAttributes as $scrub) {
            $properties['attributes'][$scrub] = "[REMOVED FOR SECURITY]";
        }

        if (static::eventsToBeRecorded()->contains('updated') && $processingEvent == 'updated') {
            $nullProperties = array_fill_keys(array_keys($properties['attributes']), null);

            $properties['old'] = array_merge($nullProperties, $this->oldAttributes);
        }

        if ($this->shouldlogOnlyDirty() && isset($properties['old'])) {
            $properties['attributes'] = array_udiff_assoc(
                                            $properties['attributes'],
                                            $properties['old'],
                                            function ($new, $old) {
                                                return $new <=> $old;
                                            }
                                        );

            $properties['old'] = collect($properties['old'])->only(array_keys($properties['attributes']))->all();
        }

        return $properties;
    }

    public static function logChanges(Model $model)
    {
        if (get_class($model) == get_class()) {
            $changes = [];
            foreach ($model->attributesToBeLogged() as $attribute) {
                if (!filter_var($attribute, FILTER_VALIDATE_EMAIL) && !str_contains($attribute, '://') && str_contains($attribute, '.')) {
                    $changes += self::getRelatedModelAttributeValue($model, $attribute);
                } else {
                    $changes += collect($model)->only($attribute)->toArray();
                }
            }

            return $changes;
        }
    }

    protected static function getRelatedModelAttributeValue(Model $model, string $attribute): array
    {
        if (substr_count($attribute, '.') > 1) {
            throw CouldNotLogChanges::invalidAttribute($attribute);
        }

        list($relatedModelName, $relatedAttribute) = explode('.', $attribute);

        $relatedModel = $model->$relatedModelName ?? $model->$relatedModelName();

        return ["{$relatedModelName}.{$relatedAttribute}" => $relatedModel->$relatedAttribute];
    }
}
