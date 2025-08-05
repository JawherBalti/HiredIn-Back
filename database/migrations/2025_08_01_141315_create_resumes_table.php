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
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('job_offer_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('status')->default('pending'); // pending, reviewed, rejected, etc.
            $table->text('cover_letter')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'job_offer_id']); // Prevent duplicate applications
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resumes');
    }
};
