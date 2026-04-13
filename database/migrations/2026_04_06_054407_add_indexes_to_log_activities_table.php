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
        Schema::table('log_activities', function (Blueprint $table) {
            //
            $table->index('opd_id');
            $table->index('timestamp');
            $table->index(['opd_id', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_activities', function (Blueprint $table) {
            //
            $table->dropIndex(['opd_id']);
            $table->dropIndex(['timestamp']);
            $table->dropIndex(['opd_id', 'timestamp']);
        });
    }
};
