<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('year');

            $table->decimal('allocated', 5, 2)->default(0);     // Jours alloués de base
            $table->decimal('used', 5, 2)->default(0);          // Jours utilisés (validés)
            $table->decimal('pending', 5, 2)->default(0);       // Jours en attente de validation
            $table->decimal('carried_over', 5, 2)->default(0);  // Report N-1
            $table->decimal('adjustment', 5, 2)->default(0);    // Ajustement manuel admin

            $table->timestamps();

            $table->unique(['user_id', 'leave_type_id', 'year'], 'unique_user_type_year');
            $table->index(['company_id', 'year'], 'idx_company_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
