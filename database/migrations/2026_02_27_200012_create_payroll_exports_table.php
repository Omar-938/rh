<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Période au format "2026-01"
            $table->string('period', 7);

            $table->enum('status', ['draft', 'validated', 'sent', 'corrected'])->default('draft');

            // Validation
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            // Envoi
            $table->timestamp('sent_at')->nullable();
            $table->json('sent_to')->nullable();  // ["comptable@cabinet.fr"]

            // Fichier généré
            $table->enum('format', ['pdf', 'xlsx', 'csv'])->default('pdf');
            $table->string('file_path', 500)->nullable();

            // Correction
            $table->boolean('is_correction')->default(false);
            $table->foreignId('correction_of_id')
                ->nullable()
                ->constrained('payroll_exports')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'period']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_exports');
    }
};
