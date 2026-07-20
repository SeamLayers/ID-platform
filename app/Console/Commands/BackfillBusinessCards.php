<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\CardProvisioningService;
use Illuminate\Console\Command;

/**
 * Gives a card to every employee who predates auto-provisioning.
 *
 * The mobile app provisions lazily the first time an employee opens their card,
 * so nobody is blocked without this. It exists so the dashboard's "employees
 * without a card" figure can be cleared in one go, rather than trickling down
 * as people happen to open the app.
 */
class BackfillBusinessCards extends Command
{
    protected $signature = 'cards:backfill
                            {--company= : Limit to one company id}
                            {--dry-run  : List who would get a card, change nothing}';

    protected $description = 'Create a draft business card for every employee who does not have one';

    public function handle(CardProvisioningService $provisioning): int
    {
        $employees = Employee::query()
            ->whereDoesntHave('businessCard')
            ->when($this->option('company'), fn ($q, $id) => $q->where('company_id', $id))
            ->with('company')
            ->get();

        if ($employees->isEmpty()) {
            $this->info('Every employee already has a card.');

            return self::SUCCESS;
        }

        $this->info($employees->count() . ' employee(s) without a card.');

        if ($this->option('dry-run')) {
            $this->table(
                ['id', 'name', 'company'],
                $employees->map(fn ($e) => [$e->id, $e->name, $e->company?->name ?? '—'])->all()
            );

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($employees->count());
        $bar->start();

        foreach ($employees as $employee) {
            // provisionFor never throws — it logs and returns null when there is
            // no company to hang a template off, so one bad row can't abort the
            // whole run.
            $provisioning->provisionFor($employee) ? $created++ : $skipped++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Created: {$created}");

        if ($skipped > 0) {
            $this->warn("Skipped: {$skipped} (see the log — usually an employee with no company)");
        }

        return self::SUCCESS;
    }
}
