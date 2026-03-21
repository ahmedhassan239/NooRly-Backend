<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Notifications\Campaigns\NotificationCampaign;
use App\Domain\Notifications\Campaigns\NotificationCampaignDelivery;
use App\Domain\Notifications\Campaigns\NotificationCampaignService;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationCampaignController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly NotificationCampaignService $campaignService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $paginator = NotificationCampaign::query()
            ->with('creator:id,name,email')
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->successResponse([
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:64'],
            'audience_type' => ['required', 'string', Rule::in([
                'all_users', 'active_users', 'inactive_users', 'notifications_enabled',
                'journey_week', 'onboarding_incomplete', 'language', 'platform', 'selected_users',
            ])],
            'audience_filters' => ['nullable', 'array'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'body_ar' => ['nullable', 'string'],
            'body_en' => ['nullable', 'string'],
            'route' => ['nullable', 'string', 'max:512'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'priority' => ['nullable', 'string', 'max:32'],
            'send_mode' => ['required', Rule::in(['now', 'scheduled'])],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        if (($validated['send_mode'] ?? '') === 'scheduled' && empty($validated['scheduled_for'])) {
            return $this->errorResponse('scheduled_for is required when send_mode is scheduled', 422);
        }

        if (
            empty($validated['title_ar']) && empty($validated['title_en'])
            && empty($validated['body_ar']) && empty($validated['body_en'])
        ) {
            return $this->errorResponse('At least one of title or body (any language) is required', 422);
        }

        $status = $validated['send_mode'] === 'scheduled' ? 'scheduled' : 'draft';

        $campaign = $this->campaignService->createDraft([
            'type' => $validated['type'],
            'audience_type' => $validated['audience_type'],
            'audience_filters' => $validated['audience_filters'] ?? null,
            'title_ar' => $validated['title_ar'] ?? null,
            'title_en' => $validated['title_en'] ?? null,
            'body_ar' => $validated['body_ar'] ?? null,
            'body_en' => $validated['body_en'] ?? null,
            'route' => $validated['route'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'priority' => $validated['priority'] ?? null,
            'send_mode' => $validated['send_mode'],
            'scheduled_for' => $validated['scheduled_for'] ?? null,
            'status' => $status,
        ], null);

        if ($validated['send_mode'] === 'now') {
            $this->campaignService->dispatchProcess($campaign);
        }

        return $this->successResponse($campaign->fresh(), 'Campaign created', 201);
    }

    public function show(string $id): JsonResponse
    {
        $campaign = NotificationCampaign::query()->with('creator:id,name,email')->findOrFail($id);

        return $this->successResponse($campaign);
    }

    public function send(string $id): JsonResponse
    {
        $campaign = NotificationCampaign::query()->findOrFail($id);
        if (! in_array($campaign->status, ['draft', 'scheduled'], true)) {
            return $this->errorResponse('Campaign cannot be sent in current status', 422);
        }

        if ($campaign->status === 'scheduled') {
            $campaign->update([
                'send_mode' => 'now',
                'scheduled_for' => null,
            ]);
        }

        $this->campaignService->dispatchProcess($campaign->fresh());

        return $this->successResponse(null, 'Campaign queued for processing');
    }

    public function cancel(string $id): JsonResponse
    {
        $campaign = NotificationCampaign::query()->findOrFail($id);
        $this->campaignService->cancel($campaign);

        return $this->successResponse($campaign->fresh(), 'Campaign cancelled');
    }

    public function deliveries(Request $request, string $id): JsonResponse
    {
        NotificationCampaign::query()->findOrFail($id);
        $perPage = min(200, max(1, (int) $request->query('per_page', 50)));

        $paginator = NotificationCampaignDelivery::query()
            ->where('campaign_id', $id)
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->successResponse([
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
