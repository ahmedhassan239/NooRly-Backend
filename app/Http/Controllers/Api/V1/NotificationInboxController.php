<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notifications\Campaigns\NotificationInbox;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationInboxController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min(100, max(1, (int) $request->query('per_page', 30)));

        $paginator = NotificationInbox::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $lang = strtolower(substr($request->attributes->get('lang') ?? $request->query('lang') ?? app()->getLocale(), 0, 2));
        if (! in_array($lang, ['ar', 'en'], true)) {
            $lang = 'en';
        }

        $items = collect($paginator->items())->map(function (NotificationInbox $row) use ($lang) {
            $title = $lang === 'ar' ? ($row->title_ar ?: $row->title_en) : ($row->title_en ?: $row->title_ar);
            $body = $lang === 'ar' ? ($row->body_ar ?: $row->body_en) : ($row->body_en ?: $row->body_ar);

            return [
                'id' => $row->id,
                'title' => $title,
                'body' => $body,
                'route' => $row->route,
                'is_read' => $row->is_read,
                'campaign_id' => $row->campaign_id,
                'created_at' => $row->created_at?->toIso8601String(),
            ];
        })->values()->all();

        return $this->successResponse([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $row = NotificationInbox::query()
            ->where('user_id', $user->id)
            ->whereKey($id)
            ->firstOrFail();

        $row->update(['is_read' => true]);

        return $this->successResponse(['id' => (int) $id, 'is_read' => true]);
    }
}
