<?php

namespace Connf\Auditlog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Connf\Auditlog\Models\Audit;
use Connf\Auditlog\Exceptions\InvalidConfiguration;

class AuditlogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/auditlog.php' => config_path('auditlog.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/auditlog.php', 'auditlog');

        if (! class_exists('CreateAuditlogTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../migrations/create_Audit_log_table.php.stub' => database_path("/migrations/{$timestamp}_Create_Audit_Log_Table.php"),
            ], 'migrations');
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->bind('command.auditlog:clean', CleanAuditlogCommand::class);

        $this->commands([
            'command.auditlog:clean',
        ]);
    }

    public static function determineAuditModel(): string
    {
        $auditModel = config('auditlog.audit_model') ?? Audit::class;

        if (! is_a($auditModel, Audit::class, true)) {
            throw InvalidConfiguration::modelIsNotValid($auditModel);
        }

        return $auditModel;
    }

    public static function getAuditModelInstance(): Model
    {
        $auditModelClassName = self::determineAuditModel();

        return new $auditModelClassName();
    }
}
