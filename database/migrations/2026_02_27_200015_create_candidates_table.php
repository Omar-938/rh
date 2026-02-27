<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')     ->constrained()->cascadeOnDelete();
            $table->foreignId('job_posting_id') ->constrained()->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('cv_path', 500)->nullable();
            $table->string('cv_original_name')->nullable();

            $table->string('stage')->default('received');
            $table->text('notes')->nullable();
            $table->timestamp('interview_date')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();  // 1–5

            $table->timestamps();

            $table->index('company_id');
            $table->index('job_posting_id');
            $table->index(['company_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
