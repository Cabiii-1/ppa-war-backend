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
        // First, update the enum column to include both old and new choices
        Schema::table('entries', function (Blueprint $table) {
            $table->enum('status', [
                'Accomplished',      // Old value
                'In Progress',       // Existing
                'Delayed',           // Existing
                'Others',            // Existing
                'Not Started',       // New values
                'On Hold',
                'Completed',
                'Cancelled',
                'Closed',
                'Testing/Review',
                'At Risk'
            ])->change();
        });

        // Then update existing data to use new enum values
        DB::table('entries')->where('status', 'Accomplished')->update(['status' => 'Completed']);
        // Other values remain the same: In Progress, Delayed, Others
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, map new values back to old ones
        DB::table('entries')->where('status', 'Completed')->update(['status' => 'Accomplished']);
        DB::table('entries')->where('status', 'Not Started')->update(['status' => 'Others']);
        DB::table('entries')->where('status', 'On Hold')->update(['status' => 'Others']);
        DB::table('entries')->where('status', 'Cancelled')->update(['status' => 'Others']);
        DB::table('entries')->where('status', 'Closed')->update(['status' => 'Others']);
        DB::table('entries')->where('status', 'Testing/Review')->update(['status' => 'Others']);
        DB::table('entries')->where('status', 'At Risk')->update(['status' => 'Others']);

        // Then revert to old enum choices
        Schema::table('entries', function (Blueprint $table) {
            $table->enum('status', ['Accomplished', 'In Progress', 'Delayed', 'Others'])->change();
        });
    }
};
