<?php

namespace MultiSms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use MultiSms\Services\SmsService;
use MultiSms\Models\SmsLog;
use MultiSms\Exceptions\SmsException;
use Illuminate\Validation\ValidationException;

class SmsController extends Controller
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send a single SMS
     */
    public function send(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'to' => 'required|string',
                'message' => 'required|string|max:1600',
                'driver' => 'nullable|string',
                'from' => 'nullable|string',
                'template_id' => 'nullable|integer|exists:sms_templates,id',
                'variables' => 'nullable|array'
            ]);

            $result = $this->smsService->send(
                $validated['to'],
                $validated['message'],
                $validated['driver'] ?? null,
                $validated['from'] ?? null,
                $validated['template_id'] ?? null,
                $validated['variables'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'SMS sent successfully',
                'data' => $result
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (SmsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'SMS sending failed',
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
     * Send bulk SMS
     */
    public function sendBulk(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'recipients' => 'required|array|min:1|max:1000',
                'recipients.*' => 'required|string',
                'message' => 'required|string|max:1600',
                'driver' => 'nullable|string',
                'from' => 'nullable|string',
                'template_id' => 'nullable|integer|exists:sms_templates,id',
                'variables' => 'nullable|array',
                'batch_size' => 'nullable|integer|min:1|max:100'
            ]);

            $results = $this->smsService->sendBulk(
                $validated['recipients'],
                $validated['message'],
                $validated['driver'] ?? null,
                $validated['from'] ?? null,
                $validated['template_id'] ?? null,
                $validated['variables'] ?? [],
                $validated['batch_size'] ?? 50
            );

            $successCount = collect($results)->where('success', true)->count();
            $failedCount = collect($results)->where('success', false)->count();

            return response()->json([
                'success' => true,
                'message' => 'Bulk SMS processing completed',
                'data' => [
                    'total' => count($results),
                    'successful' => $successCount,
                    'failed' => $failedCount,
                    'results' => $results
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (SmsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk SMS sending failed',
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
     * Get SMS status by message ID
     */
    public function status(Request $request, string $messageId): JsonResponse
    {
        try {
            $smsLog = SmsLog::where('message_id', $messageId)->first();

            if (!$smsLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMS not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'message_id' => $smsLog->message_id,
                    'to' => $smsLog->to,
                    'from' => $smsLog->from,
                    'status' => $smsLog->status,
                    'driver' => $smsLog->driver,
                    'sent_at' => $smsLog->sent_at,
                    'cost' => $smsLog->cost,
                    'error_message' => $smsLog->error_message
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
     * Get available drivers
     */
    public function drivers(): JsonResponse
    {
        try {
            $drivers = $this->smsService->getAvailableDrivers();

            return response()->json([
                'success' => true,
                'data' => $drivers
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
     * Test driver configuration
     */
    public function testDriver(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'driver' => 'required|string',
                'test_number' => 'required|string'
            ]);

            $result = $this->smsService->testDriver(
                $validated['driver'],
                $validated['test_number']
            );

            return response()->json([
                'success' => true,
                'message' => 'Driver test completed',
                'data' => $result
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (SmsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Driver test failed',
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
}