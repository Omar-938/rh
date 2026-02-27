<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table): void {
            $table->id();

            // Liens
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // user_id = l'employé censé signer (peut ne pas avoir de compte auth pour le lien public)

            // Jeton unique envoyé par email
            $table->string('token', 64)->unique();

            // Statut
            $table->enum('status', ['pending', 'signed', 'declined', 'expired'])
                  ->default('pending');

            // Signature réelle
            $table->enum('signature_type', ['drawn', 'typed'])->nullable();
            $table->text('signature_data')->nullable();
            // "drawn" → base64 du canvas PNG ; "typed" → prénom + nom saisi

            // Métadonnées de signature (preuve légère)
            $table->string('ip_address', 45)->nullable();   // IPv4 ou IPv6
            $table->string('user_agent')->nullable();
            $table->string('document_hash', 64)->nullable(); // SHA-256 du fichier au moment de signature
            $table->timestamp('signed_at')->nullable();

            // Refus
            $table->text('declined_reason')->nullable();
            $table->timestamp('declined_at')->nullable();

            // Expiration du lien de signature
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Index
            $table->index('document_id');
            $table->index('user_id');
            $table->index('token');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
