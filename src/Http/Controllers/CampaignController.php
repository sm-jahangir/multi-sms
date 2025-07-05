<?php

namespace MultiSms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use MultiSms\Models\SmsCampaign;
use MultiSms\Services\SmsService;
use MultiSms\Exceptions\SmsException;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class CampaignController extends Controller
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Get all campaigns
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SmsCampaign::query();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->where('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->where('created_at', '<=', $request->to_date);
            }

            // Search by name
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $campaigns = $query->with('template')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $campaigns
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new campaign
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'message' => 'nullable|string|max:1600',
                'recipients' => 'required|array|min:1',
                'recipients.*' => 'required|string',
                'template_id' => 'nullable|integer|exists:sms_templates,id',
                'driver' => 'nullable|string',
                'from_number' => 'nullable|string',
                'scheduled_at' => 'nullable|date|after:now',
                'settings' => 'nullable|array'
            ]);

            // If template_id is provided, message can be null
            if (!$validated['template_id'] && !$validated['message']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Either message or template_id is required'
                ], 422);
            }

            $campaign = SmsCampaign::create([
                'name' => $validated['name'],
                'message' => $validated['message'] ?? null,
                'recipients' => $validated['recipients'],
                'template_id' => $validated['template_id'] ?? null,
                'driver' => $validated['driver'] ?? null,
                'from_number' => $validated['from_number'] ?? null,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'total_recipients' => count($validated['recipients']),
                'settings' => $validated['settings'] ?? [],
                'status' => $validated['scheduled_at'] ? 'scheduled' : 'draft',
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => $campaign->load('template')
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific campaign
     */
    public function show(SmsCampaign $campaign): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $campaign->load(['template', 'logs'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a campaign
     */
    public function update(Request $request, SmsCampaign $campaign): JsonResponse
    {
        try {
            // Only allow updates for draft campaigns
            if (!in_array($campaign->status, ['draft', 'scheduled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update campaign with status: ' . $campaign->status
                ], 400);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'message' => 'nullable|string|max:1600',
                'recipients' => 'sometimes|array|min:1',
                'recipients.*' => 'required|string',
                'template_id' => 'nullable|integer|exists:sms_templates,id',
                'driver' => 'nullable|string',
                'from_number' => 'nullable|string',
                'scheduled_at' => 'nullable|date|after:now',
                'settings' => 'nullable|array'
            ]);

            if (isset($validated['recipients'])) {
                $validated['total_recipients'] = count($validated['recipients']);
            }

            $campaign->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
                'data' => $campaign->load('template')
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a campaign
     */
    public function destroy(SmsCampaign $campaign): JsonResponse
    {
        try {
            // Only allow deletion for draft campaigns
            if (!in_array($campaign->status, ['draft', 'scheduled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete campaign with status: ' . $campaign->status
                ], 400);
            }

            $campaign->delete();

            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a campaign immediately
     */
    public function start(SmsCampaign $campaign): JsonResponse
    {
        try {
            if (!in_array($campaign->status, ['draft', 'scheduled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot start campaign with status: ' . $campaign->status
                ], 400);
            }

            $campaign->start();

            // Process the campaign
            $this->processCampaign($campaign);

            return response()->json([
                'success' => true,
                'message' => 'Campaign started successfully',
                'data' => $campaign->fresh()
            ]);

        } catch (SmsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign start failed',
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a campaign
     */
    public function cancel(SmsCampaign $campaign): JsonResponse
    {
        try {
            if (!in_array($campaign->status, ['scheduled', 'running'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel campaign with status: ' . $campaign->status
                ], 400);
            }

            $campaign->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Campaign cancelled successfully',
                'data' => $campaign->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get campaign statistics
     */
    public function stats(SmsCampaign $campaign): JsonResponse
    {
        try {
            $stats = [
                'total_recipients' => $campaign->total_recipients,
                'sent_count' => $campaign->sent_count,
                'failed_count' => $campaign->failed_count,
                'pending_count' => $campaign->total_recipients - $campaign->sent_count - $campaign->failed_count,
                'success_rate' => $campaign->getSuccessRate(),
                'failure_rate' => $campaign->getFailureRate(),
                'duration' => $campaign->getDuration(),
                'status' => $campaign->status,
                'started_at' => $campaign->started_at,
                'completed_at' => $campaign->completed_at
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process campaign (send SMS to all recipients)
     */
    protected function processCampaign(SmsCampaign $campaign): void
    {
        try {
            $message = $campaign->message;
            
            // If using template, get the message from template
            if ($campaign->template_id && $campaign->template) {
                $message = $campaign->template->body;
            }

            // Send bulk SMS
            $results = $this->smsService->sendBulk(
                $campaign->recipients,
                $message,
                $campaign->driver,
                $campaign->from_number,
                $campaign->template_id,
                [],
                50 // batch size
            );

            // Update campaign counts
            $successCount = collect($results)->where('success', true)->count();
            $failedCount = collect($results)->where('success', false)->count();

            $campaign->update([
                'sent_count' => $successCount,
                'failed_count' => $failedCount
            ]);

            // Mark campaign as completed
            if ($successCount + $failedCount >= $campaign->total_recipients) {
                $campaign->complete();
            }

        } catch (\Exception $e) {
            $campaign->fail();
            throw $e;
        }
    }
}