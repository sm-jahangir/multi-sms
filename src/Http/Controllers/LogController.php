<?php

namespace MultiSms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use MultiSms\Models\SmsLog;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class LogController extends Controller
{
    /**
     * Get SMS logs with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SmsLog::query();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by driver
            if ($request->has('driver')) {
                $query->where('driver', $request->driver);
            }

            // Filter by phone number
            if ($request->has('to')) {
                $query->where('to', 'like', '%' . $request->to . '%');
            }

            // Filter by campaign
            if ($request->has('campaign_id')) {
                $query->where('campaign_id', $request->campaign_id);
            }

            // Filter by template
            if ($request->has('template_id')) {
                $query->where('template_id', $request->template_id);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Filter by sent date range
            if ($request->has('sent_from')) {
                $query->whereDate('sent_at', '>=', $request->sent_from);
            }

            if ($request->has('sent_to')) {
                $query->whereDate('sent_at', '<=', $request->sent_to);
            }

            // Search in message body
            if ($request->has('search')) {
                $query->where('body', 'like', '%' . $request->search . '%');
            }

            $logs = $query->with(['campaign', 'template'])
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
     * Get a specific SMS log
     */
    public function show(SmsLog $log): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $log->load(['campaign', 'template'])
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
     * Get SMS analytics and statistics
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'period' => 'nullable|string|in:today,yesterday,last_7_days,last_30_days,this_month,last_month,custom',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'driver' => 'nullable|string',
                'campaign_id' => 'nullable|integer'
            ]);

            $period = $validated['period'] ?? 'last_7_days';
            $query = SmsLog::query();

            // Apply date filters based on period
            switch ($period) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', Carbon::yesterday());
                    break;
                case 'last_7_days':
                    $query->where('created_at', '>=', Carbon::now()->subDays(7));
                    break;
                case 'last_30_days':
                    $query->where('created_at', '>=', Carbon::now()->subDays(30));
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('created_at', Carbon::now()->subMonth()->month)
                          ->whereYear('created_at', Carbon::now()->subMonth()->year);
                    break;
                case 'custom':
                    if ($validated['from_date']) {
                        $query->whereDate('created_at', '>=', $validated['from_date']);
                    }
                    if ($validated['to_date']) {
                        $query->whereDate('created_at', '<=', $validated['to_date']);
                    }
                    break;
            }

            // Apply additional filters
            if (isset($validated['driver'])) {
                $query->where('driver', $validated['driver']);
            }

            if (isset($validated['campaign_id'])) {
                $query->where('campaign_id', $validated['campaign_id']);
            }

            // Get basic statistics
            $totalSms = $query->count();
            $successfulSms = $query->clone()->successful()->count();
            $failedSms = $query->clone()->failed()->count();
            $pendingSms = $query->clone()->pending()->count();
            $totalCost = $query->clone()->sum('cost');

            // Get statistics by driver
            $byDriver = $query->clone()
                ->selectRaw('driver, COUNT(*) as total, SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as successful')
                ->groupBy('driver')
                ->get();

            // Get daily statistics for the period
            $dailyStats = $query->clone()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as successful')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Get hourly statistics for today
            $hourlyStats = [];
            if ($period === 'today') {
                $hourlyStats = $query->clone()
                    ->selectRaw('HOUR(created_at) as hour, COUNT(*) as total, SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as successful')
                    ->whereDate('created_at', Carbon::today())
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->get();
            }

            // Get top recipients
            $topRecipients = $query->clone()
                ->selectRaw('`to` as phone_number, COUNT(*) as total')
                ->groupBy('to')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            // Calculate rates
            $successRate = $totalSms > 0 ? round(($successfulSms / $totalSms) * 100, 2) : 0;
            $failureRate = $totalSms > 0 ? round(($failedSms / $totalSms) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_sms' => $totalSms,
                        'successful_sms' => $successfulSms,
                        'failed_sms' => $failedSms,
                        'pending_sms' => $pendingSms,
                        'success_rate' => $successRate,
                        'failure_rate' => $failureRate,
                        'total_cost' => round($totalCost, 4)
                    ],
                    'by_driver' => $byDriver,
                    'daily_stats' => $dailyStats,
                    'hourly_stats' => $hourlyStats,
                    'top_recipients' => $topRecipients,
                    'period' => $period
                ]
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
     * Export SMS logs
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'format' => 'nullable|string|in:csv,json',
                'status' => 'nullable|string',
                'driver' => 'nullable|string',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'campaign_id' => 'nullable|integer',
                'limit' => 'nullable|integer|min:1|max:10000'
            ]);

            $format = $validated['format'] ?? 'csv';
            $limit = $validated['limit'] ?? 1000;

            $query = SmsLog::query();

            // Apply filters
            if (isset($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (isset($validated['driver'])) {
                $query->where('driver', $validated['driver']);
            }

            if (isset($validated['from_date'])) {
                $query->whereDate('created_at', '>=', $validated['from_date']);
            }

            if (isset($validated['to_date'])) {
                $query->whereDate('created_at', '<=', $validated['to_date']);
            }

            if (isset($validated['campaign_id'])) {
                $query->where('campaign_id', $validated['campaign_id']);
            }

            $logs = $query->with(['campaign:id,name', 'template:id,name'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Format data for export
            $exportData = $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'to' => $log->to,
                    'from' => $log->from,
                    'body' => $log->body,
                    'driver' => $log->driver,
                    'status' => $log->status,
                    'sent_at' => $log->sent_at?->format('Y-m-d H:i:s'),
                    'campaign' => $log->campaign?->name,
                    'template' => $log->template?->name,
                    'cost' => $log->cost,
                    'message_id' => $log->message_id,
                    'error_message' => $log->error_message,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'format' => $format,
                    'total_records' => $exportData->count(),
                    'export_data' => $exportData
                ]
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
     * Delete old logs
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'days' => 'required|integer|min:1|max:365'
            ]);

            $cutoffDate = Carbon::now()->subDays($validated['days']);
            $deletedCount = SmsLog::where('created_at', '<', $cutoffDate)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Log cleanup completed',
                'data' => [
                    'deleted_count' => $deletedCount,
                    'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s')
                ]
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
}