<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCompanySettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingsController extends Controller
{
    // ─── Page paramètres ────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $company = $request->user()->company;

        return Inertia::render('Settings/Company', [
            'company' => [
                'name'               => $company->name,
                'siret'              => $company->siret,
                'address'            => $company->address,
                'city'               => $company->city,
                'postal_code'        => $company->postal_code,
                'phone'              => $company->phone,
                'logo_url'           => $company->logo_url,
                'primary_color'      => $company->primary_color ?? '#1B4F72',
                'work_hours_per_day' => $company->work_hours_per_day,
                'work_days_per_week' => $company->work_days_per_week,
                'timezone'           => $company->timezone,
                'accountant_emails'  => $company->getAccountantEmails(),
            ],
        ]);
    }

    // ─── Mise à jour ─────────────────────────────────────────────────────────────

    /**
     * Sauvegarde une section de paramètres à la fois.
     */
    public function update(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $company = $request->user()->company;
        $data    = $request->validated();
        $section = $data['section'];

        match ($section) {
            'general'    => $this->saveGeneral($company, $data),
            'branding'   => $this->saveBranding($company, $request, $data),
            'hr'         => $this->saveHr($company, $data),
            'accountant' => $this->saveAccountant($company, $data),
        };

        return back()->with('success', 'Paramètres enregistrés avec succès.');
    }

    // ─── Suppression du logo ─────────────────────────────────────────────────────

    public function deleteLogo(Request $request): RedirectResponse
    {
        $company = $request->user()->company;

        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
            $company->logo_path = null;
            $company->save();
        }

        return back()->with('success', 'Logo supprimé.');
    }

    // ─── Helpers privés ──────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $data
     */
    private function saveGeneral(\App\Models\Company $company, array $data): void
    {
        $company->fill([
            'name'        => $data['name'],
            'siret'       => $data['siret']       ?? null,
            'address'     => $data['address']     ?? null,
            'city'        => $data['city']        ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'phone'       => $data['phone']       ?? null,
        ])->save();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveBranding(
        \App\Models\Company $company,
        UpdateCompanySettingsRequest $request,
        array $data
    ): void {
        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $company->logo_path = $request->file('logo')->store('logos', 'public');
        }

        if (isset($data['primary_color'])) {
            $company->primary_color = $data['primary_color'];
        }

        $company->save();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveHr(\App\Models\Company $company, array $data): void
    {
        $company->updateSettings([
            'work_hours_per_day' => (float) ($data['work_hours_per_day'] ?? 7),
            'work_days_per_week' => (int)   ($data['work_days_per_week'] ?? 5),
            'timezone'           => $data['timezone'] ?? 'Europe/Paris',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveAccountant(\App\Models\Company $company, array $data): void
    {
        $emails = array_values(array_filter(
            $data['accountant_emails'] ?? [],
            fn (string $e) => filter_var($e, FILTER_VALIDATE_EMAIL) !== false
        ));

        $company->updateSettings(['accountant_emails' => $emails]);
    }
}
