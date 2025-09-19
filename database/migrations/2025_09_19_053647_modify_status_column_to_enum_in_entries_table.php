<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear existing data to avoid enum constraint issues
        DB::table('entries')->delete();

        Schema::table('entries', function (Blueprint $table) {
            $table->enum('status', ['Accomplished', 'In Progress', 'Delayed', 'Others'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->string('status')->change();
        });
    }
};
