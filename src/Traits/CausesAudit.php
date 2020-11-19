<?php

namespace Connf\Auditlog\Traits;

use Connf\Auditlog\AuditlogServiceProvider;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CausesAudit
{
    public function Audit(): MorphMany
    {
        return $this->morphMany(AuditlogServiceProvider::determineAuditModel(), 'causer');
    }

    /** @deprecated Use Audit() instead */
    public function loggedAudit(): MorphMany
    {
        return $this->Audit();
    }
}
