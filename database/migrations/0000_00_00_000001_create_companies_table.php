<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            // Identité
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('siret', 14)->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->default('#1B4F72');

            // Abonnement
            $table->enum('plan', ['trial', 'starter', 'business', 'enterprise'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();

            // Paramètres JSON (timezone, heures/jour, règles heures sup, etc.)
            $table->json('settings')->nullable();

            // Stripe (Cashier)
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
