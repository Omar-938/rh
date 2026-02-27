<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Département & hiérarchie (FK ajoutée dans create_departments_table)
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();

            // Identité
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // 2FA (Fortify)
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            // Rôle RBAC
            $table->enum('role', ['admin', 'manager', 'employee'])->default('employee')->index();

            // Coordonnées
            $table->string('phone', 20)->nullable();
            $table->string('avatar_path')->nullable();

            // Contrat
            $table->date('hire_date')->nullable();
            $table->enum('contract_type', ['cdi', 'cdd', 'interim', 'stage', 'alternance'])->default('cdi');
            $table->date('contract_end_date')->nullable();
            $table->date('trial_end_date')->nullable();
            $table->decimal('weekly_hours', 4, 1)->default(35.0);

            // Matricule interne
            $table->string('employee_id', 50)->nullable();

            // Informations personnelles
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 10)->nullable();

            // Données sensibles (chiffrées en DB)
            $table->text('social_security_number')->nullable(); // AES-256
            $table->text('iban')->nullable();                   // AES-256

            // Contact d'urgence
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();

            // Statut
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Contraintes et index
            $table->unique(['email', 'company_id']);
            $table->index(['company_id', 'role']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
