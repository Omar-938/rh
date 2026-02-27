<?php

declare(strict_types=1);

use App\Enums\OvertimeStatus;
use App\Models\OvertimeEntry;

// ═══════════════════════════════════════════════════════════════════════════════
// HEURES SUPPLÉMENTAIRES — LISTE
// ═══════════════════════════════════════════════════════════════════════════════

describe('Heures sup — Liste', function () {

    it('affiche la liste pour un admin', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->get('/heures-supplementaires')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Overtime/Index'));
    });

    it('affiche la liste pour un employé', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);

        $this->actingAs($employee)
            ->get('/heures-supplementaires')
            ->assertStatus(200);
    });

    it('un employé ne voit que ses propres entrées', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);
        $other    = createEmployee($company);

        OvertimeEntry::factory()->create([
            'user_id'    => $employee->id,
            'company_id' => $company->id,
            'date'       => '2026-01-10',
            'hours'      => 2.0,
            'rate'       => '25',
            'source'     => 'manual',
            'status'     => OvertimeStatus::Pending,
        ]);
        OvertimeEntry::factory()->create([
            'user_id'    => $other->id,
            'company_id' => $company->id,
            'date'       => '2026-01-11',
            'hours'      => 1.5,
            'rate'       => '25',
            'source'     => 'manual',
            'status'     => OvertimeStatus::Pending,
        ]);

        $this->actingAs($employee)
            ->get('/heures-supplementaires')
            ->assertInertia(fn ($page) => $page
                ->component('Overtime/Index')
                ->where('counts.all', 1)
            );
    });

    it('un admin voit toutes les entrées de l\'entreprise', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee1 = createEmployee($company);
        $employee2 = createEmployee($company);

        OvertimeEntry::factory()->create([
            'user_id'    => $employee1->id,
            'company_id' => $company->id,
            'date'       => '2026-01-10',
            'hours'      => 2.0,
            'rate'       => '25',
            'source'     => 'manual',
            'status'     => OvertimeStatus::Pending,
        ]);
        OvertimeEntry::factory()->create([
            'user_id'    => $employee2->id,
            'company_id' => $company->id,
            'date'       => '2026-01-11',
            'hours'      => 1.5,
            'rate'       => '25',
            'source'     => 'manual',
            'status'     => OvertimeStatus::Approved,
        ]);

        $this->actingAs($admin)
            ->get('/heures-supplementaires')
            ->assertInertia(fn ($page) => $page
                ->component('Overtime/Index')
                ->where('counts.all', 2)
            );
    });

});

// ═══════════════════════════════════════════════════════════════════════════════
// HEURES SUPPLÉMENTAIRES — DÉCLARATION
// ═══════════════════════════════════════════════════════════════════════════════

describe('Heures sup — Déclaration', function () {

    it('affiche le formulaire de déclaration', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->get('/heures-supplementaires/declarer')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Overtime/Declare'));
    });

    it('un employé peut déclarer des heures supplémentaires', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);

        $this->actingAs($employee)
            ->post('/heures-supplementaires/declarer', [
                'date'         => '2026-01-15',
                'hours'        => 2.5,
                'rate'         => '25',
                'reason'       => 'Urgence client sur le projet',
                'compensation' => 'payment',
            ])->assertRedirect('/heures-supplementaires');

        $this->assertDatabaseHas('overtime_entries', [
            'user_id'    => $employee->id,
            'company_id' => $company->id,
            'status'     => OvertimeStatus::Pending->value,
        ]);
    });

    it('refuse une déclaration sans date', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->post('/heures-supplementaires/declarer', [
                'hours'        => 2.5,
                'rate'         => '25',
                'reason'       => 'Urgence client sur le projet',
                'compensation' => 'payment',
            ])->assertInvalid(['date']);
    });

    it('refuse une déclaration avec moins de 0.25 heures', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->post('/heures-supplementaires/declarer', [
                'date'         => '2026-01-15',
                'hours'        => 0.1,
                'rate'         => '25',
                'reason'       => 'Urgence client sur le projet',
                'compensation' => 'payment',
            ])->assertInvalid(['hours']);
    });

    it('refuse une déclaration sans motif', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->post('/heures-supplementaires/declarer', [
                'date'         => '2026-01-15',
                'hours'        => 2.5,
                'rate'         => '25',
                'compensation' => 'payment',
            ])->assertInvalid(['reason']);
    });

});

// ═══════════════════════════════════════════════════════════════════════════════
// HEURES SUPPLÉMENTAIRES — APPROBATION / REJET
// ═══════════════════════════════════════════════════════════════════════════════

describe('Heures sup — Approbation et rejet', function () {

    it('un admin peut approuver une déclaration en attente', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);

        $overtime = OvertimeEntry::factory()->create([
            'user_id'    => $employee->id,
            'company_id' => $company->id,
            'date'       => '2026-01-10',
            'hours'      => 2.0,
            'rate'       => '25',
            'source'     => 'manual',
            'status'     => OvertimeStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->post("/heures-supplementaires/{$overtime->id}/approuver", [
                'comment' => 'OK',
            ])->assertRedirect();

        expect($overtime->fresh()->status)->toBe(OvertimeStatus::Approved);
    });

    it('un admin peut rejeter une déclaration en attente', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);

        $overtime = OvertimeEntry::factory()->create([
            'user_id'    => $employee->id,
            'company_id' => $company->id,
            'date'       => '2026-01-10',
            'hours'      => 2.0,
            'rate'       => '25',
            'source'     => 'manual',
            'status'     => OvertimeStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->post("/heures-supplementaires/{$overtime->id}/rejeter", [
                'comment' => 'Non justifié',
            ])->assertRedirect();

        expect($overtime->fresh()->status)->toBe(OvertimeStatus::Rejected);
    });

    it('un manager peut approuver une déclaration', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $manager  = createManager($company);
        $employee = createEmployee($company);

        $overtime = OvertimeEntry::factory()->create([
            'user_id'    => $employee->id,
            'company_id' => $company->id,
            'date'       => '2026-01-10',
            'hours'      => 1.5,
            'rate'       => '25',
            'source'     => 'manual',
            'status'     => OvertimeStatus::Pending,
        ]);

        $this->actingAs($manager)
            ->post("/heures-supplementaires/{$overtime->id}/approuver", [
                'comment' => '',
            ])->assertRedirect();

        expect($overtime->fresh()->status)->toBe(OvertimeStatus::Approved);
    });

    it('un employé ne peut pas approuver une déclaration', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);
        $other    = createEmployee($company);

        $overtime = OvertimeEntry::factory()->create([
            'user_id'    => $other->id,
            'company_id' => $company->id,
            'date'       => '2026-01-10',
            'hours'      => 2.0,
            'rate'       => '25',
            'source'     => 'manual',
            'status'     => OvertimeStatus::Pending,
        ]);

        $this->actingAs($employee)
            ->post("/heures-supplementaires/{$overtime->id}/approuver", [
                'comment' => '',
            ])->assertStatus(403);

        expect($overtime->fresh()->status)->toBe(OvertimeStatus::Pending);
    });

});

// ═══════════════════════════════════════════════════════════════════════════════
// HEURES SUPPLÉMENTAIRES — ISOLATION MULTI-TENANT
// ═══════════════════════════════════════════════════════════════════════════════

describe('Heures sup — Isolation multi-tenant', function () {

    it('un admin ne peut pas voir les heures sup d\'une autre entreprise', function () {
        [$companyA, $adminA] = createCompanyWithAdmin();
        [$companyB, $adminB] = createCompanyWithAdmin();

        OvertimeEntry::factory()->create([
            'user_id'    => $adminB->id,
            'company_id' => $companyB->id,
            'date'       => '2026-01-10',
            'hours'      => 3.0,
            'rate'       => '25',
            'source'     => 'manual',
            'status'     => OvertimeStatus::Pending,
        ]);

        $this->actingAs($adminA)
            ->get('/heures-supplementaires')
            ->assertInertia(fn ($page) => $page->where('counts.all', 0));
    });

});
