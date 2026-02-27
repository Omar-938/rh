<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->enum('start_half', ['morning', 'afternoon'])->nullable();
            $table->enum('end_half',   ['morning', 'afternoon'])->nullable();
            $table->decimal('days_count', 4, 1);

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])
                  ->default('pending');

            $table->text('employee_comment')->nullable();
            $table->text('reviewer_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status'],         'idx_user_status');
            $table->index(['company_id', 'status'],      'idx_company_status');
            $table->index(['company_id', 'start_date', 'end_date'], 'idx_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
