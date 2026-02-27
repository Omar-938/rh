<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\SignatureCertificateService;
use App\Services\SignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private SignatureService $signatureService,
        private SignatureCertificateService $certificateService,
    ) {}

    /**
     * Liste des documents avec filtres, recherche et tri.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // ── Paramètres de filtrage ────────────────────────────────────────────
        $search   = trim($request->query('search', ''));
        $category = $request->query('category', 'all');
        $filter   = $request->query('filter', 'all');
        $userId   = $request->query('user_id', '');
        $sort     = $request->query('sort', 'created_at');
        $dir      = $request->query('dir', 'desc');

        // Colonnes de tri autorisées
        $allowedSorts = ['created_at', 'name', 'file_size', 'category', 'expires_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }
        $dir = $dir === 'asc' ? 'asc' : 'desc';

        // ── Query de base ─────────────────────────────────────────────────────
        $baseQuery = Document::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at');

        // Employé → seulement ses documents + docs d'entreprise
        if (! $user->isAdmin() && ! $user->isManager()) {
            $baseQuery->where(fn ($q) =>
                $q->where('user_id', $user->id)->orWhereNull('user_id')
            );
        }

        // ── Compteurs par catégorie (avant filtre catégorie) ─────────────────
        $categoryCounts = (clone $baseQuery)
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        // ── Compteurs filtres rapides ─────────────────────────────────────────
        $filterCounts = [
            'all'       => (clone $baseQuery)->count(),
            'signature' => (clone $baseQuery)->pendingSignature()->count(),
            'expiring'  => (clone $baseQuery)->expiringSoon()->count(),
            'expired'   => (clone $baseQuery)->where('expires_at', '<', now()->toDateString())->count(),
            'company'   => (clone $baseQuery)->companyWide()->count(),
        ];

        // ── Application des filtres ───────────────────────────────────────────
        $query = clone $baseQuery;

        // Recherche textuelle
        if ($search !== '') {
            $query->where(fn ($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('original_filename', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
            );
        }

        // Filtre catégorie
        if ($category !== 'all') {
            $query->where('category', $category);
        }

        // Filtres rapides
        match ($filter) {
            'signature' => $query->pendingSignature(),
            'expiring'  => $query->expiringSoon(),
            'expired'   => $query->where('expires_at', '<', now()->toDateString()),
            'company'   => $query->companyWide(),
            default     => null,
        };

        // Filtre employé (admin/manager uniquement)
        if ($userId !== '' && ($user->isAdmin() || $user->isManager())) {
            if ($userId === 'company') {
                $query->companyWide();
            } else {
                $query->where('user_id', (int) $userId);
            }
        }

        // ── Tri ───────────────────────────────────────────────────────────────
        // Les documents sans expires_at sont toujours en bas quand on trie par date expiration
        if ($sort === 'expires_at') {
            $query->orderByRaw("expires_at IS NULL")->orderBy('expires_at', $dir);
        } else {
            $query->orderBy($sort, $dir);
        }

        // ── Pagination + sérialisation ────────────────────────────────────────
        $documents = $query
            ->with(['user', 'uploadedBy'])
            ->paginate(20)
            ->withQueryString();

        // ── Employés pour le sélecteur de filtre (admin/manager) ────────────
        $employees = [];
        if ($user->isAdmin() || $user->isManager()) {
            $employees = User::withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->get()
                ->map(fn (User $u) => [
                    'id'       => $u->id,
                    'name'     => $u->full_name,
                    'initials' => $u->initials,
                ])
                ->toArray();
        }

        return Inertia::render('Documents/Index', [
            'documents'      => $documents->through(fn (Document $d) => $this->documentService->serialize($d)),
            'category_counts'=> $categoryCounts,
            'filter_counts'  => $filterCounts,
            'employees'      => $employees,
            'is_uploader'    => $user->isAdmin() || $user->isManager(),
            'filters'        => compact('search', 'category', 'filter', 'userId', 'sort', 'dir'),
        ]);
    }

    /**
     * Formulaire d'upload.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        $employees = [];
        if ($user->isAdmin() || $user->isManager()) {
            $employees = User::withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->map(fn (User $u) => [
                    'id'       => $u->id,
                    'name'     => $u->full_name,
                    'initials' => $u->initials,
                ])
                ->toArray();
        }

        return Inertia::render('Documents/Upload', [
            'employees' => $employees,
        ]);
    }

    /**
     * Traite l'upload, chiffre et stocke le document.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $user      = $request->user();
        $validated = $request->validated();

        // Si un user_id est fourni, vérifier qu'il appartient bien à la même société
        if (! empty($validated['user_id'])) {
            User::withoutGlobalScopes()
                ->where('id', $validated['user_id'])
                ->where('company_id', $user->company_id)
                ->firstOrFail();
        }

        // Les employés ne peuvent uploader que pour eux-mêmes
        if (! $user->isAdmin() && ! $user->isManager()) {
            $validated['user_id'] = $user->id;
        }

        $document = $this->documentService->upload(
            file:     $request->file('file'),
            uploader: $user,
            data:     $validated,
        );

        return redirect()->route('documents.index')->with('flash', [
            'type'    => 'success',
            'message' => "Document « {$document->name} » importé avec succès.",
        ]);
    }

    /**
     * Génère et télécharge le certificat PDF de signature électronique.
     */
    public function downloadCertificate(Request $request, Document $document): StreamedResponse
    {
        $user = $request->user();
        abort_unless($this->documentService->canAccess($document, $user), 403);
        abort_unless($document->requires_signature, 404, 'Ce document ne possède pas de certificat de signature.');

        return $this->certificateService->download($document);
    }

    /**
     * Téléchargement sécurisé avec déchiffrement à la volée.
     */
    public function download(Request $request, Document $document): StreamedResponse
    {
        abort_unless(
            $this->documentService->canAccess($document, $request->user()),
            403,
            'Accès non autorisé à ce document.'
        );

        return $this->documentService->download($document);
    }

    /**
     * Page de détail d'un document avec historique des signatures.
     */
    public function show(Request $request, Document $document): Response
    {
        $user = $request->user();
        abort_unless($this->documentService->canAccess($document, $user), 403);

        $document->load(['user', 'uploadedBy', 'signatures.user']);

        $signatures = $document->signatures
            ->sortBy('created_at')
            ->map(fn ($s) => $this->signatureService->serializeSignature($s))
            ->values();

        return Inertia::render('Documents/Show', [
            'document'   => $this->documentService->serialize($document),
            'signatures' => $signatures,
            'can_manage' => $user->isAdmin() || $user->isManager(),
        ]);
    }

    /**
     * Renvoie un nouveau lien de signature (révoque le précédent).
     */
    public function resendSignature(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();

        abort_unless($document->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);
        abort_unless($document->requires_signature, 422);
        abort_unless($document->user_id !== null, 422);

        $this->signatureService->resend($document, $user);

        return redirect()->route('documents.show', $document->id)->with('flash', [
            'type'    => 'success',
            'message' => "Nouveau lien de signature envoyé à {$document->user?->full_name}.",
        ]);
    }

    /**
     * Révoque toutes les demandes de signature pending.
     */
    public function revokeSignature(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();

        abort_unless($document->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $this->signatureService->revoke($document);

        return redirect()->route('documents.show', $document->id)->with('flash', [
            'type'    => 'info',
            'message' => "Demande de signature annulée pour « {$document->name} ».",
        ]);
    }

    /**
     * Envoie une demande de signature à l'employé associé au document.
     */
    public function requestSignature(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();

        abort_unless($document->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);
        abort_unless($document->requires_signature, 422, 'Ce document ne nécessite pas de signature.');
        abort_unless($document->user_id !== null, 422, 'Aucun signataire associé à ce document.');

        $this->signatureService->requestSignature($document, $user);

        return back()->with('flash', [
            'type'    => 'success',
            'message' => "Demande de signature envoyée pour « {$document->name} ».",
        ]);
    }

    /**
     * Suppression douce + suppression physique du fichier chiffré.
     */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();

        abort_unless($document->company_id === $user->company_id, 403);
        abort_unless(
            $user->isAdmin() || $user->isManager()
            || ($document->uploaded_by === $user->id && $document->user_id === $user->id),
            403
        );

        $name = $document->name;
        $this->documentService->deleteFile($document);
        $document->delete();

        return back()->with('flash', [
            'type'    => 'success',
            'message' => "Document « {$name} » supprimé.",
        ]);
    }
}
