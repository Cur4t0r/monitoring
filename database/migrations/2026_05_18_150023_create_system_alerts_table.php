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
        Schema::create('system_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('message');
            $table->enum('severity', ['critical', 'warning', 'info'])->default('info');
            $table->enum('status', ['active', 'acknowledged', 'resolved'])->default('active');
            $table->foreignId('opd_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_alerts');
    }
};
