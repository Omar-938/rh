<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_export_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_export_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // JSON avec toutes les variables compilées :
            // days_worked, days_absent, absences[], overtime{}, variables[], notes
            $table->json('data');

            // TRUE si le RH a modifié manuellement les données compilées
            $table->boolean('is_modified')->default(false);

            $table->timestamps();

            $table->index('payroll_export_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_export_lines');
    }
};
