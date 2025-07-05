<?php

namespace MultiSms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use MultiSms\Models\SmsAutoresponder;
use MultiSms\Models\SmsTrigger;
use MultiSms\Models\SmsAutomationLog;
use MultiSms\Services\SmsService;
use MultiSms\Exceptions\SmsException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AutoresponderController extends Controller
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Get all autoresponders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SmsAutoresponder::query();

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by trigger type
            if ($request->has('trigger_type')) {
                $query->where('trigger_type', $request->trigger_type);
            }

            // Search by name
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $autoresponders = $query->with('template')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $autoresponders
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
     * Create a new autoresponder
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'trigger_type' => 'required|string|in:keyword,incoming_sms,missed_call,webhook',
                'trigger_value' => 'required|array',
                'response_message' => 'nullable|string|max:1600',
                'template_id' => 'nullable|integer|exists:sms_templates,id',
                'delay_minutes' => 'nullable|integer|min:0|max:1440',
                'max_triggers_per_number' => 'nullable|integer|min:0',
                'conditions' => 'nullable|array',
                'settings' => 'nullable|array',
                'is_active' => 'boolean'
            ]);

            // Validate that either response_message or template_id is provided
            if (!$validated['response_message'] && !$validated['template_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Either response_message or template_id is required'
                ], 422);
            }

            $autoresponder = SmsAutoresponder::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Autoresponder created successfully',
                'data' => $autoresponder->load('template')
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
     * Get a specific autoresponder
     */
    public function show(SmsAutoresponder $autoresponder): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $autoresponder->load(['template', 'triggers', 'automationLogs'])
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
     * Update an autoresponder
     */
    public function update(Request $request, SmsAutoresponder $autoresponder): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'trigger_type' => 'sometimes|string|in:keyword,incoming_sms,missed_call,webhook',
                'trigger_value' => 'sometimes|array',
                'response_message' => 'nullable|string|max:1600',
                'template_id' => 'nullable|integer|exists:sms_templates,id',
                'delay_minutes' => 'nullable|integer|min:0|max:1440',
                'max_triggers_per_number' => 'nullable|integer|min:0',
                'conditions' => 'nullable|array',
                'settings' => 'nullable|array',
                'is_active' => 'boolean'
            ]);

            $autoresponder->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Autoresponder updated successfully',
                'data' => $autoresponder->fresh()->load('template')
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
     * Delete an autoresponder
     */
    public function destroy(SmsAutoresponder $autoresponder): JsonResponse
    {
        try {
            $autoresponder->delete();

            return response()->json([
                'success' => true,
                'message' => 'Autoresponder deleted successfully'
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
     * Toggle autoresponder active status
     */
    public function toggleStatus(SmsAutoresponder $autoresponder): JsonResponse
    {
        try {
            $autoresponder->update([
                'is_active' => !$autoresponder->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Autoresponder status updated successfully',
                'data' => [
                    'id' => $autoresponder->id,
                    'is_active' => $autoresponder->is_active
                ]
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
     * Test autoresponder with sample data
     */
    public function test(Request $request, SmsAutoresponder $autoresponder): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone_number' => 'required|string',
                'trigger_data' => 'nullable|array',
                'variables' => 'nullable|array'
            ]);

            $phoneNumber = $validated['phone_number'];
            $triggerData = $validated['trigger_data'] ?? [];
            $variables = $validated['variables'] ?? [];

            // Check if autoresponder matches the trigger
            if (!$autoresponder->matchesTrigger($autoresponder->trigger_type, $triggerData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Autoresponder does not match the provided trigger data'
                ], 400);
            }

            // Check if autoresponder can be triggered for this phone number
            if (!$autoresponder->canTriggerForPhoneNumber($phoneNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Autoresponder has reached maximum triggers for this phone number'
                ], 400);
            }

            // Get response message
            $responseMessage = $autoresponder->getResponseMessage($variables);

            // Create test trigger
            $trigger = SmsTrigger::create([
                'autoresponder_id' => $autoresponder->id,
                'phone_number' => $phoneNumber,
                'trigger_type' => $autoresponder->trigger_type,
                'trigger_data' => $triggerData
            ]);

            // Send test SMS
            try {
                $result = $this->smsService->send(
                    $phoneNumber,
                    $responseMessage,
                    null, // use default driver
                    null, // use default from number
                    $autoresponder->template_id
                );

                $trigger->markAsProcessed($result['message_id'], $responseMessage);

                return response()->json([
                    'success' => true,
                    'message' => 'Autoresponder test completed successfully',
                    'data' => [
                        'trigger_id' => $trigger->id,
                        'response_message' => $responseMessage,
                        'sms_result' => $result
                    ]
                ]);

            } catch (SmsException $e) {
                $trigger->update(['error_message' => $e->getMessage()]);

                return response()->json([
                    'success' => false,
                    'message' => 'SMS sending failed during test',
                    'error' => $e->getMessage(),
                    'data' => [
                        'trigger_id' => $trigger->id,
                        'response_message' => $responseMessage
                    ]
                ], 400);
            }

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
     * Get autoresponder triggers
     */
    public function triggers(Request $request, SmsAutoresponder $autoresponder): JsonResponse
    {
        try {
            $query = $autoresponder->triggers();

            // Filter by phone number
            if ($request->has('phone_number')) {
                $query->forPhoneNumber($request->phone_number);
            }

            // Filter by processed status
            if ($request->has('processed')) {
                if ($request->boolean('processed')) {
                    $query->processed();
                } else {
                    $query->unprocessed();
                }
            }

            // Filter by success status
            if ($request->has('successful')) {
                if ($request->boolean('successful')) {
                    $query->successful();
                } else {
                    $query->failed();
                }
            }

            $triggers = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $triggers
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
     * Get autoresponder automation logs
     */
    public function logs(Request $request, SmsAutoresponder $autoresponder): JsonResponse
    {
        try {
            $query = $autoresponder->automationLogs();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by phone number
            if ($request->has('phone_number')) {
                $query->forPhoneNumber($request->phone_number);
            }

            // Filter by trigger type
            if ($request->has('trigger_type')) {
                $query->byTriggerType($request->trigger_type);
            }

            $logs = $query->with('trigger')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $logs
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
     * Get autoresponder statistics
     */
    public function stats(SmsAutoresponder $autoresponder): JsonResponse
    {
        try {
            $totalTriggers = $autoresponder->triggers()->count();
            $processedTriggers = $autoresponder->triggers()->processed()->count();
            $successfulTriggers = $autoresponder->triggers()->successful()->count();
            $failedTriggers = $autoresponder->triggers()->failed()->count();

            $totalLogs = $autoresponder->automationLogs()->count();
            $successfulLogs = $autoresponder->automationLogs()->successful()->count();
            $failedLogs = $autoresponder->automationLogs()->failed()->count();

            $stats = [
                'triggers' => [
                    'total' => $totalTriggers,
                    'processed' => $processedTriggers,
                    'successful' => $successfulTriggers,
                    'failed' => $failedTriggers,
                    'success_rate' => $totalTriggers > 0 ? round(($successfulTriggers / $totalTriggers) * 100, 2) : 0
                ],
                'automation_logs' => [
                    'total' => $totalLogs,
                    'successful' => $successfulLogs,
                    'failed' => $failedLogs,
                    'success_rate' => $totalLogs > 0 ? round(($successfulLogs / $totalLogs) * 100, 2) : 0
                ],
                'autoresponder' => [
                    'is_active' => $autoresponder->is_active,
                    'trigger_type' => $autoresponder->trigger_type,
                    'max_triggers_per_number' => $autoresponder->max_triggers_per_number,
                    'delay_minutes' => $autoresponder->delay_minutes
                ]
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
}