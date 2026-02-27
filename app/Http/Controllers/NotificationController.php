<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Page complète des notifications.
     */
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(30)
            ->through(fn (DatabaseNotification $n) => [
                'id'         => $n->id,
                'title'      => $n->data['title']      ?? '',
                'body'       => $n->data['body']       ?? '',
                'icon'       => $n->data['icon']       ?? '🔔',
                'type'       => $n->data['type']       ?? 'info',
                'action_url' => $n->data['action_url'] ?? null,
                'is_read'    => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        return Inertia::render('Notifications/Index', [
            'notifications'  => $notifications,
            'unread_count'   => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Marque une notification comme lue et redirige vers son action_url.
     */
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        $actionUrl = $notification->data['action_url'] ?? null;

        return $actionUrl
            ? redirect($actionUrl)
            : back();
    }

    /**
     * Marque toutes les notifications comme lues.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    /**
     * Retourne les notifications récentes non lues en JSON (pour le panel TopBar).
     */
    public function recent(Request $request): JsonResponse
    {
        $items = $request->user()
            ->notifications()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (DatabaseNotification $n) => [
                'id'         => $n->id,
                'title'      => $n->data['title']      ?? '',
                'body'       => $n->data['body']       ?? '',
                'icon'       => $n->data['icon']       ?? '🔔',
                'action_url' => $n->data['action_url'] ?? null,
                'is_read'    => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'items'        => $items,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
