<?php

return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ENABLE_AUDIT_LOGGING', true),

    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => 3650,

    /*
     * If no log name is passed to the Audit() helper
     * we use this default log name.
     */
    'default_log_name' => 'audit_auto',

    /*
     * You can specify an auth driver here that gets user models.
     * If this is null we'll use the default Laravel auth driver.
     */
    'default_auth_driver' => null,

    /*
     * If set to true, the subject returns soft deleted models.
     */
    'subject_returns_soft_deleted_models' => false,

    /*
     * This model will be used to log Audit. The only requirement is that
     * it should be or extend the Connf\Auditlog\Models\Audit model.
     */
    'audit_model' => \Connf\Auditlog\Models\Audit::class,
];
