<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->foreignId('donation_id')->constrained('donations')->onDelete('cascade');
            $table->foreignId('request_id')->constrained('requests')->onDelete('cascade');
            $table->foreignId('donor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('quantity');
            $table->string('claim_code', 50)->unique();
            $table->string('status', 50)->default('pending_claim'); // pending_claim, claimed, completed, cancelled, expired
            $table->string('pickup_method', 50)->default('pickup');
            $table->date('pickup_date')->nullable();
            $table->string('pickup_time', 50)->nullable();
            $table->string('pickup_address', 500)->nullable();
            $table->timestamp('claim_deadline')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'requester_id']);
            $table->index(['status', 'donor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
