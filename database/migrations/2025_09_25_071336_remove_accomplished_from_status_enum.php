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
        // First, ensure any remaining 'Accomplished' values are updated to 'Completed'
        DB::table('entries')->where('status', 'Accomplished')->update(['status' => 'Completed']);

        // Then update the enum column to remove 'Accomplished'
        Schema::table('entries', function (Blueprint $table) {
            $table->enum('status', [
                'Not Started',
                'In Progress',
                'On Hold',
                'Completed',
                'Cancelled',
                'Closed',
                'Testing/Review',
                'Delayed',
                'At Risk',
                'Others'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert by adding 'Accomplished' back to the enum
        Schema::table('entries', function (Blueprint $table) {
            $table->enum('status', [
                'Accomplished',      // Add back for rollback
                'Not Started',
                'In Progress',
                'On Hold',
                'Completed',
                'Cancelled',
                'Closed',
                'Testing/Review',
                'Delayed',
                'At Risk',
                'Others'
            ])->change();
        });
    }
};
