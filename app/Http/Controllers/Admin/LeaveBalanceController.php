<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaveBalanceController extends Controller
{
    /**
     * Ajuste manuellement le solde d'un employé pour un type de congé.
     * Le champ `adjustment` représente le delta cumulé des ajustements admins.
     */
    public function adjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id'       => ['required', 'integer', 'exists:users,id'],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'year'          => ['required', 'integer', 'min:2020', 'max:2100'],
            'delta'         => ['required', 'numeric', 'min:-365', 'max:365'],
            'reason'        => ['nullable', 'string', 'max:255'],
        ], [
            'user_id.exists'       => 'Employé introuvable.',
            'leave_type_id.exists' => 'Type de congé introuvable.',
            'delta.required'       => 'Le nombre de jours à ajuster est obligatoire.',
            'delta.numeric'        => 'La valeur doit être un nombre (ex: 2, -1, 0.5).',
            'delta.min'            => 'L\'ajustement ne peut pas dépasser -365 jours.',
            'delta.max'            => 'L\'ajustement ne peut pas dépasser +365 jours.',
        ]);

        // Vérifier que l'employé appartient à la même company
        $employee = User::findOrFail($data['user_id']);
        if ($employee->company_id !== $request->user()->company_id) {
            throw ValidationException::withMessages(['user_id' => 'Accès non autorisé.']);
        }

        $balance = LeaveBalance::firstOrCreate(
            [
                'user_id'       => $data['user_id'],
                'leave_type_id' => $data['leave_type_id'],
                'year'          => $data['year'],
            ],
            [
                'company_id'   => $request->user()->company_id,
                'allocated'    => 0,
                'used'         => 0,
                'pending'      => 0,
                'carried_over' => 0,
                'adjustment'   => 0,
            ]
        );

        $delta = (float) $data['delta'];

        // S'assurer que l'ajustement total ne descend pas trop bas
        $newAdjustment = (float) $balance->adjustment + $delta;
        if ($balance->total_allocated + $delta < 0) {
            return back()->withErrors([
                'delta' => 'L\'ajustement rendrait le solde total négatif.',
            ]);
        }

        $balance->increment('adjustment', $delta);

        $sign    = $delta >= 0 ? '+' : '';
        $message = sprintf(
            'Solde de %s ajusté de %s%.1f jour(s).%s',
            $employee->full_name,
            $sign,
            $delta,
            $data['reason'] ? ' Motif : ' . $data['reason'] : ''
        );

        return back()->with('success', $message);
    }

    /**
     * Crée ou remet à zéro le solde d'un employé pour une année.
     */
    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id'       => ['required', 'integer', 'exists:users,id'],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'year'          => ['required', 'integer', 'min:2020', 'max:2100'],
            'allocated'     => ['required', 'numeric', 'min:0', 'max:365'],
        ], [
            'allocated.required' => 'Le nombre de jours alloués est obligatoire.',
            'allocated.min'      => 'L\'allocation ne peut pas être négative.',
        ]);

        $employee = User::findOrFail($data['user_id']);
        if ($employee->company_id !== $request->user()->company_id) {
            abort(403);
        }

        LeaveBalance::updateOrCreate(
            [
                'user_id'       => $data['user_id'],
                'leave_type_id' => $data['leave_type_id'],
                'year'          => $data['year'],
            ],
            [
                'company_id'   => $request->user()->company_id,
                'allocated'    => $data['allocated'],
                'used'         => 0,
                'pending'      => 0,
                'carried_over' => 0,
                'adjustment'   => 0,
            ]
        );

        return back()->with('success', "Solde de {$employee->full_name} réinitialisé à {$data['allocated']} j.");
    }
}
