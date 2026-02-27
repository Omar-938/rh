<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('color', 7)->default('#2E86C1');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('company_id');
        });

        // Ajout de la contrainte FK department_id sur users (MySQL uniquement — SQLite ne supporte pas ALTER TABLE ADD CONSTRAINT)
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('department_id')
                      ->references('id')
                      ->on('departments')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
            });
        }

        Schema::dropIfExists('departments');
    }
};
