<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for performance optimization
        Schema::table('weekly_reports', function (Blueprint $table) {
            // Check if index exists before adding
            if (!$this->indexExists('weekly_reports', 'idx_weekly_reports_employee_id')) {
                $table->index('employee_id', 'idx_weekly_reports_employee_id');
            }
            if (!$this->indexExists('weekly_reports', 'idx_weekly_reports_period')) {
                $table->index(['period_start', 'period_end'], 'idx_weekly_reports_period');
            }
            if (!$this->indexExists('weekly_reports', 'idx_weekly_reports_status')) {
                $table->index('status', 'idx_weekly_reports_status');
            }
            if (!$this->indexExists('weekly_reports', 'idx_weekly_reports_submitted_at')) {
                $table->index('submitted_at', 'idx_weekly_reports_submitted_at');
            }
        });

        Schema::table('entries', function (Blueprint $table) {
            if (!$this->indexExists('entries', 'idx_entries_employee_id')) {
                $table->index('employee_id', 'idx_entries_employee_id');
            }
            if (!$this->indexExists('entries', 'idx_entries_entry_date')) {
                $table->index('entry_date', 'idx_entries_entry_date');
            }
            if (!$this->indexExists('entries', 'idx_entries_status')) {
                $table->index('status', 'idx_entries_status');
            }
            if (!$this->indexExists('entries', 'idx_entries_employee_date')) {
                $table->index(['employee_id', 'entry_date'], 'idx_entries_employee_date');
            }
        });

        // Add unique constraints for data integrity
        Schema::table('weekly_reports', function (Blueprint $table) {
            if (!$this->indexExists('weekly_reports', 'uk_weekly_reports_employee_period')) {
                $table->unique(['employee_id', 'period_start', 'period_end'], 'uk_weekly_reports_employee_period');
            }
        });

        Schema::table('entries', function (Blueprint $table) {
            if (!$this->indexExists('entries', 'uk_entries_employee_date')) {
                $table->unique(['employee_id', 'entry_date'], 'uk_entries_employee_date');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $index) {
            if ($index->Key_name === $indexName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_reports', function (Blueprint $table) {
            $table->dropIndex('idx_weekly_reports_employee_id');
            $table->dropIndex('idx_weekly_reports_period');
            $table->dropIndex('idx_weekly_reports_status');
            $table->dropIndex('idx_weekly_reports_submitted_at');
            $table->dropUnique('uk_weekly_reports_employee_period');
        });

        Schema::table('entries', function (Blueprint $table) {
            $table->dropIndex('idx_entries_employee_id');
            $table->dropIndex('idx_entries_entry_date');
            $table->dropIndex('idx_entries_status');
            $table->dropIndex('idx_entries_employee_date');
            $table->dropUnique('uk_entries_employee_date');
        });
    }
};