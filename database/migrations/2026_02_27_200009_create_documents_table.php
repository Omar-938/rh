<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();

            // Appartenance
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // NULL = document d'entreprise (règlement intérieur, charte, etc.)
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();

            // Métadonnées
            $table->string('name');
            $table->string('original_filename');
            $table->enum('category', [
                'contract', 'amendment', 'certificate', 'rules',
                'medical', 'identity', 'rib', 'review', 'other',
            ])->default('other');
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size');          // Octets

            // Stockage chiffré (hors public/)
            $table->string('file_path', 500);              // relatif à storage/app/
            $table->boolean('is_encrypted')->default(true);

            // Signature
            $table->boolean('requires_signature')->default(false);
            $table->enum('signature_status', ['none', 'pending', 'partial', 'completed'])
                  ->default('none');

            // Divers
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Index
            $table->index('company_id');
            $table->index('user_id');
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'signature_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
