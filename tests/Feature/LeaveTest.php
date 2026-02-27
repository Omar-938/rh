<?php

declare(strict_types=1);

use App\Enums\AcquisitionType;
use App\Enums\LeaveStatus;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;

// ═══════════════════════════════════════════════════════════════════════════════
// CONGÉS — LISTE
// ═══════════════════════════════════════════════════════════════════════════════

describe('Congés — Liste', function () {

    it('affiche la liste des congés pour un admin', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->get('/conges')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Leaves/Index'));
    });

    it('affiche la liste des congés pour un employé', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);

        $this->actingAs($employee)
            ->get('/conges')
            ->assertStatus(200);
    });

    it('un employé ne voit que ses propres demandes', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);
        $other    = createEmployee($company);

        $leaveType = LeaveType::factory()->create([
            'company_id' => $company->id,
        ]);

        $mine   = LeaveRequest::factory()->create([
            'user_id'       => $employee->id,
            'company_id'    => $company->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2026-07-01',
            'end_date'      => '2026-07-05',
            'days_count'    => 5,
            'status'        => LeaveStatus::Pending,
        ]);
        $theirs = LeaveRequest::factory()->create([
            'user_id'       => $other->id,
            'company_id'    => $company->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2026-08-01',
            'end_date'      => '2026-08-03',
            'days_count'    => 3,
            'status'        => LeaveStatus::Pending,
        ]);

        $this->actingAs($employee)
            ->get('/conges')
            ->assertInertia(fn ($page) => $page
                ->component('Leaves/Index')
                ->where('requests.total', 1)
            );
    });

});

// ═══════════════════════════════════════════════════════════════════════════════
// CONGÉS — DEMANDE
// ═══════════════════════════════════════════════════════════════════════════════

describe('Congés — Demande', function () {

    it('affiche le formulaire de demande', function () {
        [$company, $admin] = createCompanyWithAdmin();

        LeaveType::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)
            ->get('/conges/demande')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Leaves/Request'));
    });

    it('crée une demande de congé et redirige vers la liste', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $leaveType = LeaveType::factory()->create([
            'company_id'       => $company->id,
            'requires_approval'=> true,
            'acquisition_type' => AcquisitionType::None, // sans solde requis
        ]);

        LeaveBalance::factory()->create([
            'user_id'       => $admin->id,
            'company_id'    => $company->id,
            'leave_type_id' => $leaveType->id,
            'year'          => now()->year,
            'allocated'     => 25,
            'used'          => 0,
            'pending'       => 0,
        ]);

        // Lundi au vendredi (5 jours ouvrés)
        $this->actingAs($admin)
            ->post('/conges', [
                'leave_type_id'    => $leaveType->id,
                'start_date'       => '2026-07-06', // lundi
                'end_date'         => '2026-07-10', // vendredi
                'employee_comment' => 'Vacances été',
            ])->assertRedirect('/conges');

        $this->assertDatabaseHas('leave_requests', [
            'user_id'       => $admin->id,
            'company_id'    => $company->id,
            'leave_type_id' => $leaveType->id,
            'status'        => LeaveStatus::Pending->value,
        ]);
    });

    it('refuse une demande sans type de congé', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->post('/conges', [
                'leave_type_id' => null,
                'start_date'    => '2026-07-06',
                'end_date'      => '2026-07-10',
            ])->assertInvalid(['leave_type_id']);
    });

    it('refuse une demande dont la date de fin est avant la date de début', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)
            ->post('/conges', [
                'leave_type_id' => $leaveType->id,
                'start_date'    => '2026-07-10',
                'end_date'      => '2026-07-06',
            ])->assertInvalid(['end_date']);
    });

});

// ═══════════════════════════════════════════════════════════════════════════════
// CONGÉS — APPROBATION / REJET
// ═══════════════════════════════════════════════════════════════════════════════

describe('Congés — Approbation et rejet', function () {

    it('un admin peut approuver une demande en attente', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);

        $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);
        $leave     = LeaveRequest::factory()->create([
            'user_id'       => $employee->id,
            'company_id'    => $company->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2026-07-01',
            'end_date'      => '2026-07-05',
            'days_count'    => 5,
            'status'        => LeaveStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->post("/conges/{$leave->id}/approuver", ['comment' => ''])
            ->assertRedirect('/conges');

        expect($leave->fresh()->status)->toBe(LeaveStatus::Approved);
    });

    it('un admin peut rejeter une demande en attente', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);

        $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);
        $leave     = LeaveRequest::factory()->create([
            'user_id'       => $employee->id,
            'company_id'    => $company->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2026-07-01',
            'end_date'      => '2026-07-05',
            'days_count'    => 5,
            'status'        => LeaveStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->post("/conges/{$leave->id}/rejeter", ['comment' => 'Période chargée'])
            ->assertRedirect('/conges');

        expect($leave->fresh()->status)->toBe(LeaveStatus::Rejected);
    });

    it('un employé ne peut pas approuver une demande', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);
        $other    = createEmployee($company);

        $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);
        $leave     = LeaveRequest::factory()->create([
            'user_id'       => $other->id,
            'company_id'    => $company->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2026-07-01',
            'end_date'      => '2026-07-05',
            'days_count'    => 5,
            'status'        => LeaveStatus::Pending,
        ]);

        $this->actingAs($employee)
            ->post("/conges/{$leave->id}/approuver", ['comment' => ''])
            ->assertStatus(403);

        expect($leave->fresh()->status)->toBe(LeaveStatus::Pending);
    });

    it('ne peut pas approuver une demande déjà approuvée', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);

        $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);
        $leave     = LeaveRequest::factory()->create([
            'user_id'       => $employee->id,
            'company_id'    => $company->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2026-07-01',
            'end_date'      => '2026-07-05',
            'days_count'    => 5,
            'status'        => LeaveStatus::Approved,
        ]);

        $this->actingAs($admin)
            ->post("/conges/{$leave->id}/approuver", ['comment' => ''])
            ->assertInvalid(['status']);
    });

    it('un employé peut annuler sa propre demande en attente', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);

        $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);
        $leave     = LeaveRequest::factory()->create([
            'user_id'       => $employee->id,
            'company_id'    => $company->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2026-07-01',
            'end_date'      => '2026-07-05',
            'days_count'    => 5,
            'status'        => LeaveStatus::Pending,
        ]);

        $this->actingAs($employee)
            ->delete("/conges/{$leave->id}")
            ->assertRedirect();

        expect($leave->fresh()->status)->toBe(LeaveStatus::Cancelled);
    });

});

// ═══════════════════════════════════════════════════════════════════════════════
// CONGÉS — ISOLATION MULTI-TENANT
// ═══════════════════════════════════════════════════════════════════════════════

describe('Congés — Isolation multi-tenant', function () {

    it('un admin ne peut pas voir les demandes d\'une autre entreprise', function () {
        [$companyA, $adminA] = createCompanyWithAdmin();
        [$companyB, $adminB] = createCompanyWithAdmin();

        $leaveTypeB = LeaveType::factory()->create(['company_id' => $companyB->id]);
        $leaveB     = LeaveRequest::factory()->create([
            'user_id'       => $adminB->id,
            'company_id'    => $companyB->id,
            'leave_type_id' => $leaveTypeB->id,
            'start_date'    => '2026-07-01',
            'end_date'      => '2026-07-05',
            'days_count'    => 5,
            'status'        => LeaveStatus::Pending,
        ]);

        // AdminA ne doit pas voir la demande de l'entreprise B
        $this->actingAs($adminA)
            ->get('/conges')
            ->assertInertia(fn ($page) => $page->where('requests.total', 0));
    });

    it('un admin ne peut pas approuver la demande d\'une autre entreprise', function () {
        [$companyA, $adminA] = createCompanyWithAdmin();
        [$companyB, $adminB] = createCompanyWithAdmin();

        $leaveTypeB = LeaveType::factory()->create(['company_id' => $companyB->id]);
        $leaveB     = LeaveRequest::factory()->create([
            'user_id'       => $adminB->id,
            'company_id'    => $companyB->id,
            'leave_type_id' => $leaveTypeB->id,
            'start_date'    => '2026-07-01',
            'end_date'      => '2026-07-05',
            'days_count'    => 5,
            'status'        => LeaveStatus::Pending,
        ]);

        // AdminA tente d'approuver → 404 (route model binding respects global scope)
        $this->actingAs($adminA)
            ->post("/conges/{$leaveB->id}/approuver", ['comment' => ''])
            ->assertStatus(404);
    });

});
