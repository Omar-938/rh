<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Signature;
use App\Services\SignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SignatureController extends Controller
{
    public function __construct(private SignatureService $signatureService) {}

    /**
     * Page publique de signature (accessible sans authentification via token unique).
     */
    public function show(string $token): Response
    {
        $data = $this->signatureService->getSigningData($token);

        return Inertia::render('Documents/Sign', $data);
    }

    /**
     * Enregistre la signature.
     */
    public function sign(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:drawn,typed'],
            'data' => ['required', 'string', 'max:500000'],
            // "drawn" → base64 PNG (peut être long) ; "typed" → prénom nom (court)
        ]);

        $signature = Signature::where('token', $token)->firstOrFail();

        $this->signatureService->sign(
            signature: $signature,
            type:      $validated['type'],
            data:      $validated['data'],
            ip:        $request->ip() ?? '',
            userAgent: $request->userAgent() ?? '',
        );

        return redirect()->route('signature.show', $token)->with('flash', [
            'type'    => 'success',
            'message' => 'Votre signature a été enregistrée avec succès.',
        ]);
    }

    /**
     * Enregistre le refus de signature.
     */
    public function decline(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $signature = Signature::where('token', $token)->firstOrFail();

        $this->signatureService->decline(
            signature: $signature,
            reason:    $validated['reason'] ?? null,
            ip:        $request->ip() ?? '',
            userAgent: $request->userAgent() ?? '',
        );

        return redirect()->route('signature.show', $token)->with('flash', [
            'type'    => 'info',
            'message' => 'Votre refus a été enregistré.',
        ]);
    }
}
