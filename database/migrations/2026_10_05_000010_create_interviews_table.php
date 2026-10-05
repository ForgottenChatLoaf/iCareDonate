<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->nullable()->constrained('requests')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('scheduled_date');
            $table->string('scheduled_time', 50);
            $table->string('method', 50)->default('online_call'); // online_call, in_person, phone
            $table->string('meeting_link_or_location', 255)->nullable();
            $table->text('questions')->nullable();
            $table->text('notes')->nullable();
            $table->string('result', 50)->default('pending'); // pending, verified, needs_information, further_review, not_verified
            $table->foreignId('conducted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
