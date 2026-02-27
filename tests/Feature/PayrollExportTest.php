<?php

declare(strict_types=1);

use App\Enums\PayrollExportStatus;
use App\Models\PayrollExport;

// ═══════════════════════════════════════════════════════════════════════════════
// EXPORT PAIE — ACCÈS
// ═══════════════════════════════════════════════════════════════════════════════

describe('Export paie — Accès', function () {

    it('affiche l\'historique des exports pour un admin', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->get('/exports-paie')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('PayrollExport/Index'));
    });

    it('affiche l\'historique des exports pour un manager', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $manager = createManager($company);

        $this->actingAs($manager)
            ->get('/exports-paie')
            ->assertStatus(200);
    });

    it('interdit l\'accès à un employé', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);

        $this->actingAs($employee)
            ->get('/exports-paie')
            ->assertStatus(403);
    });

});

// ═══════════════════════════════════════════════════════════════════════════════
// EXPORT PAIE — GÉNÉRATION
// ═══════════════════════════════════════════════════════════════════════════════

describe('Export paie — Génération', function () {

    it('génère un export pour une période valide', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->post('/exports-paie/generer', [
                'period' => '2026-01',
            ])->assertRedirect();

        $this->assertDatabaseHas('payroll_exports', [
            'company_id' => $company->id,
            'period'     => '2026-01',
        ]);
    });

    it('retourne l\'export existant si la période est déjà générée (idempotent)', function () {
        [$company, $admin] = createCompanyWithAdmin();

        // Premier appel
        $this->actingAs($admin)
            ->post('/exports-paie/generer', ['period' => '2026-02']);

        $firstCount = PayrollExport::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('period', '2026-02')
            ->count();

        // Deuxième appel
        $this->actingAs($admin)
            ->post('/exports-paie/generer', ['period' => '2026-02']);

        $secondCount = PayrollExport::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('period', '2026-02')
            ->count();

        expect($firstCount)->toBe(1)
            ->and($secondCount)->toBe(1); // toujours un seul
    });

    it('refuse une période dans un format invalide', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->post('/exports-paie/generer', ['period' => 'janvier-2026'])
            ->assertInvalid(['period']);
    });

    it('refuse une génération sans période', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->post('/exports-paie/generer', [])
            ->assertInvalid(['period']);
    });

    it('un employé ne peut pas générer d\'export', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);

        $this->actingAs($employee)
            ->post('/exports-paie/generer', ['period' => '2026-01'])
            ->assertStatus(403);
    });

    it('l\'export généré est en statut brouillon', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->post('/exports-paie/generer', ['period' => '2026-03']);

        $export = PayrollExport::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('period', '2026-03')
            ->first();

        expect($export)->not->toBeNull()
            ->and($export->status)->toBe(PayrollExportStatus::Draft);
    });

});

// ═══════════════════════════════════════════════════════════════════════════════
// EXPORT PAIE — RÉVISION / VALIDATION
// ═══════════════════════════════════════════════════════════════════════════════

describe('Export paie — Révision et validation', function () {

    it('affiche la page de révision d\'un export', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $export = PayrollExport::factory()->create([
            'company_id' => $company->id,
            'period'     => '2026-01',
            'status'     => PayrollExportStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->get("/exports-paie/{$export->id}")
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('PayrollExport/Review'));
    });

    it('un admin peut valider un export brouillon', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $export = PayrollExport::factory()->create([
            'company_id' => $company->id,
            'period'     => '2026-01',
            'status'     => PayrollExportStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->post("/exports-paie/{$export->id}/valider")
            ->assertRedirect();

        expect($export->fresh()->status)->toBe(PayrollExportStatus::Validated);
    });

    it('un admin peut recompiler un export brouillon', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $export = PayrollExport::factory()->create([
            'company_id' => $company->id,
            'period'     => '2026-01',
            'status'     => PayrollExportStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->post("/exports-paie/{$export->id}/recompiler")
            ->assertRedirect();
    });

    it('ne peut pas valider un export déjà validé', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $export = PayrollExport::factory()->create([
            'company_id' => $company->id,
            'period'     => '2026-01',
            'status'     => PayrollExportStatus::Validated,
        ]);

        $this->actingAs($admin)
            ->post("/exports-paie/{$export->id}/valider")
            ->assertStatus(422);
    });

    it('ne peut pas recompiler un export validé', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $export = PayrollExport::factory()->create([
            'company_id' => $company->id,
            'period'     => '2026-01',
            'status'     => PayrollExportStatus::Validated,
        ]);

        $this->actingAs($admin)
            ->post("/exports-paie/{$export->id}/recompiler")
            ->assertStatus(422);
    });

});

// ═══════════════════════════════════════════════════════════════════════════════
// EXPORT PAIE — ISOLATION MULTI-TENANT
// ═══════════════════════════════════════════════════════════════════════════════

describe('Export paie — Isolation multi-tenant', function () {

    it('un admin ne peut pas voir l\'export d\'une autre entreprise', function () {
        [$companyA, $adminA] = createCompanyWithAdmin();
        [$companyB, $adminB] = createCompanyWithAdmin();

        $exportB = PayrollExport::factory()->create([
            'company_id' => $companyB->id,
            'period'     => '2026-01',
            'status'     => PayrollExportStatus::Draft,
        ]);

        // La portée globale cache l'enregistrement → 404 (pas de fuite d'info)
        $this->actingAs($adminA)
            ->get("/exports-paie/{$exportB->id}")
            ->assertStatus(404);
    });

    it('un admin ne peut pas valider l\'export d\'une autre entreprise', function () {
        [$companyA, $adminA] = createCompanyWithAdmin();
        [$companyB, $adminB] = createCompanyWithAdmin();

        $exportB = PayrollExport::factory()->create([
            'company_id' => $companyB->id,
            'period'     => '2026-01',
            'status'     => PayrollExportStatus::Draft,
        ]);

        // La portée globale cache l'enregistrement → 404
        $this->actingAs($adminA)
            ->post("/exports-paie/{$exportB->id}/valider")
            ->assertStatus(404);
    });

});
