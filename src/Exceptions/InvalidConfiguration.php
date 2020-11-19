<?php

namespace Connf\Auditlog\Exceptions;

use Exception;
use Connf\Auditlog\Models\Audit;

class InvalidConfiguration extends Exception
{
    public static function modelIsNotValid(string $className)
    {
        return new static("The given model class `$className` does not extend `".Audit::class.'`');
    }
}
