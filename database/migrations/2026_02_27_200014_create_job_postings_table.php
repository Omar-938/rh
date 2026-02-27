<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')    ->constrained()->cascadeOnDelete();
            $table->foreignId('department_id') ->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')    ->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->string('contract_type')->default('cdi');
            $table->string('location')->nullable();
            $table->string('salary_range', 100)->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index('company_id');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
