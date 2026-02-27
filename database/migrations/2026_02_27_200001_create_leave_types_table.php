<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('name');                              // "Congés payés", "RTT", etc.
            $table->string('slug');
            $table->string('color', 7)->default('#3498DB');

            $table->decimal('days_per_year', 5, 2)->default(25.00);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);

            $table->enum('acquisition_type', ['monthly', 'annual', 'none'])->default('monthly');

            $table->unsignedInteger('max_consecutive_days')->nullable(); // Limite jours consécutifs
            $table->unsignedInteger('notice_days')->default(0);          // Délai de prévenance
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['slug', 'company_id']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
