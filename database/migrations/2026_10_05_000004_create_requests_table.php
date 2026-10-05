<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('donation_id')->nullable()->constrained('donations')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('item_name', 200);
            $table->unsignedInteger('quantity_requested');
            $table->unsignedInteger('quantity_matched')->default(0);
            $table->text('purpose');
            $table->string('urgency_level', 50)->default('medium'); // low, medium, high, emergency
            $table->string('status', 50)->default('submitted'); // submitted, proof_review, needs_information, approved, rejected, matched, claimed, completed, cancelled, expired
            $table->string('contact_number', 50)->nullable();
            $table->string('delivery_address', 500)->nullable();
            $table->boolean('interview_required')->default(false);
            $table->text('interview_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('info_request')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'requester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
