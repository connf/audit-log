<?php

use Connf\Auditlog\AuditLogger;

if (! function_exists('audit')) {
    function audit(string $logName = null): AuditLogger
    {
        $defaultLogName = config('auditlog.default_log_name');

        return app(AuditLogger::class)->useLog($logName ?? $defaultLogName);
    }
}
