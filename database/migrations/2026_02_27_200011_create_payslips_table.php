<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table): void {
            $table->id();

            // Appartenance
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // NULL = bulletin non associé (non reconnu à l'import)
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();

            // Période
            $table->unsignedSmallInteger('period_year');   // ex: 2025
            $table->unsignedTinyInteger('period_month');   // 1–12

            // Fichier chiffré
            $table->string('original_filename');
            $table->string('file_path', 500);
            $table->unsignedInteger('file_size');
            $table->boolean('is_encrypted')->default(true);

            // Statut distribution
            $table->enum('status', ['draft', 'distributed'])->default('draft');
            $table->timestamp('distributed_at')->nullable();

            // Consultation employé
            $table->boolean('is_viewed')->default(false);
            $table->timestamp('viewed_at')->nullable();

            // Divers
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Index pour les requêtes fréquentes
            $table->index(['company_id', 'user_id']);
            $table->index(['company_id', 'period_year', 'period_month']);
            $table->index(['company_id', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
