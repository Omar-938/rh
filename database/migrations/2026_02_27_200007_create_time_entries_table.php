<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')   ->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->date('date');

            $table->timestamp('clock_in') ->nullable();
            $table->timestamp('break_start')->nullable();   // Début pause en cours
            $table->timestamp('break_end')  ->nullable();   // Fin de la dernière pause
            $table->timestamp('clock_out')->nullable();

            $table->decimal('total_hours',  4, 2)->nullable();  // Calculé à clock_out
            $table->unsignedInteger('total_break_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')   ->default(0);  // Étape 19

            $table->enum('source', ['manual', 'clock', 'import'])->default('clock');
            $table->string('ip_address', 45)->nullable();
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 11, 8)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'date'], 'uniq_user_date');
            $table->index(['company_id', 'date'], 'idx_te_company_date');
            $table->index(['user_id', 'date'],    'idx_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
