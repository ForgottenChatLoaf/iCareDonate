<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->unsignedInteger('quantity_total');
            $table->unsignedInteger('quantity_reserved')->default(0);
            $table->unsignedInteger('quantity_claimed')->default(0);
            $table->string('unit', 50)->default('pcs');
            $table->string('condition_type', 50)->default('good'); // new, like_new, good, fair
            $table->date('expiration_date')->nullable();
            $table->string('pickup_method', 50)->default('pickup'); // pickup, delivery, both
            $table->string('pickup_address', 500)->nullable();
            $table->string('pickup_barangay', 150)->nullable();
            $table->string('pickup_city', 100)->nullable();
            $table->string('pickup_province', 100)->nullable();
            $table->text('pickup_notes')->nullable();
            $table->string('pickup_schedule', 255)->nullable();
            $table->string('donor_name', 150)->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->string('status', 50)->default('pending_verification'); // pending_verification, needs_information, approved, rejected, fully_allocated, expired, cancelled
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('info_request')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
