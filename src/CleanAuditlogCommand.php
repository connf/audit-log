<?php

namespace Connf\Auditlog;

use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanAuditlogCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'auditlog:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old records from the Audit Log.';

    public function handle()
    {
        $this->comment('Cleaning Audit Log...');

        $maxAgeInDays = config('auditlog.delete_records_older_than_days');

        $cutOffDate = Carbon::now()->subDays($maxAgeInDays)->format('Y-m-d H:i:s');

        $Audit = AuditlogServiceProvider::getAuditModelInstance();

        $amountDeleted = $Audit::where('created_at', '<', $cutOffDate)->delete();

        $this->info("Deleted {$amountDeleted} record(s) from the Audit Log.");

        $this->comment('All done!');
    }
}
