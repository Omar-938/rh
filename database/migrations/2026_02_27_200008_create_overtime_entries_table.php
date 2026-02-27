<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')   ->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->decimal('hours', 4, 2);                        // ex: 2.50 = 2h30
            $table->enum('rate', ['25', '50'])->default('25');     // Taux de majoration
            $table->enum('source', ['manual', 'auto'])->default('manual');
            $table->foreignId('time_entry_id')->nullable()->constrained('time_entries')->nullOnDelete();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('reason')->nullable();                    // Motif déclaré
            $table->text('reviewer_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->enum('compensation', ['payment', 'rest'])->default('payment');
            // payment = paiement majoré | rest = repos compensateur
            // Lien vers l'export de paie — table créée à l'étape 29
            $table->unsignedBigInteger('included_in_export_id')->nullable()->index();

            $table->timestamps();

            $table->index(['user_id',    'date'],   'idx_overtime_user_date');
            $table->index(['company_id', 'status'], 'idx_overtime_company_status');
            $table->index(['company_id', 'date'],   'idx_overtime_company_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_entries');
    }
};
