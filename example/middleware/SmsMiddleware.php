<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Carbon\Carbon;

/**
 * SMS Middleware
 * 
 * এই middleware SMS functionality এর জন্য authentication, authorization এবং rate limiting handle করে।
 * সব SMS related routes এ এই middleware apply করা যায়।
 */
class SmsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): ResponseAlias
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }
            
            return redirect()->route('login')
                           ->with('error', 'Please login to access SMS features');
        }
        
        $user = Auth::user();
        
        // Check user permissions if specified
        if (!empty($permissions)) {
            $hasPermission = $this->checkPermissions($user, $permissions);
            
            if (!$hasPermission) {
                Log::warning('SMS access denied', [
                    'user_id' => $user->id,
                    'required_permissions' => $permissions,
                    'route' => $request->route()->getName()
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient permissions to access this feature'
                    ], 403);
                }
                
                return redirect()->back()
                               ->with('error', 'You do not have permission to access this feature');
            }
        }
        
        // Rate limiting for SMS operations
        if ($this->isSmsOperation($request)) {
            $rateLimitResult = $this->checkRateLimit($user, $request);
            
            if (!$rateLimitResult['allowed']) {
                Log::warning('SMS rate limit exceeded', [
                    'user_id' => $user->id,
                    'route' => $request->route()->getName(),
                    'limit_info' => $rateLimitResult
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rate limit exceeded. Please try again later.',
                        'retry_after' => $rateLimitResult['retry_after'] ?? 60
                    ], 429);
                }
                
                return redirect()->back()
                               ->with('error', 'Too many SMS operations. Please wait before trying again.');
            }
        }
        
        // Log SMS activity
        $this->logActivity($user, $request);
        
        return $next($request);
    }
    
    /**
     * Check if user has required permissions
     */
    private function checkPermissions($user, array $permissions): bool
    {
        // If no specific permission system is implemented, 
        // you can use simple role-based checks or implement your own logic
        
        foreach ($permissions as $permission) {
            switch ($permission) {
                case 'sms.view':
                    // Check if user can view SMS features
                    if (!$this->canViewSms($user)) {
                        return false;
                    }
                    break;
                    
                case 'sms.send':
                    // Check if user can send SMS
                    if (!$this->canSendSms($user)) {
                        return false;
                    }
                    break;
                    
                case 'sms.templates':
                    // Check if user can manage templates
                    if (!$this->canManageTemplates($user)) {
                        return false;
                    }
                    break;
                    
                case 'sms.campaigns':
                    // Check if user can manage campaigns
                    if (!$this->canManageCampaigns($user)) {
                        return false;
                    }
                    break;
                    
                case 'sms.autoresponders':
                    // Check if user can manage autoresponders
                    if (!$this->canManageAutoresponders($user)) {
                        return false;
                    }
                    break;
                    
                case 'sms.analytics':
                    // Check if user can view analytics
                    if (!$this->canViewAnalytics($user)) {
                        return false;
                    }
                    break;
                    
                case 'sms.settings':
                    // Check if user can manage settings
                    if (!$this->canManageSettings($user)) {
                        return false;
                    }
                    break;
                    
                case 'sms.admin':
                    // Check if user has admin access
                    if (!$this->isAdmin($user)) {
                        return false;
                    }
                    break;
                    
                default:
                    // Unknown permission, deny access
                    return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if the request is an SMS operation that needs rate limiting
     */
    private function isSmsOperation(Request $request): bool
    {
        $smsOperations = [
            'sms.api.send-test',
            'sms.campaigns.start',
            'sms.autoresponders.test-trigger'
        ];
        
        $routeName = $request->route()->getName();
        
        return in_array($routeName, $smsOperations) || 
               str_contains($routeName, 'send') ||
               $request->isMethod('POST') && str_contains($request->path(), 'sms');
    }
    
    /**
     * Check rate limit for SMS operations
     */
    private function checkRateLimit($user, Request $request): array
    {
        $cacheKey = "sms_rate_limit:{$user->id}:" . date('Y-m-d-H-i');
        $maxAttempts = $this->getMaxAttempts($user, $request);
        $windowMinutes = 1; // 1 minute window
        
        $attempts = cache()->get($cacheKey, 0);
        
        if ($attempts >= $maxAttempts) {
            return [
                'allowed' => false,
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts,
                'retry_after' => 60 // seconds
            ];
        }
        
        // Increment attempts
        cache()->put($cacheKey, $attempts + 1, now()->addMinutes($windowMinutes));
        
        return [
            'allowed' => true,
            'attempts' => $attempts + 1,
            'max_attempts' => $maxAttempts,
            'remaining' => $maxAttempts - ($attempts + 1)
        ];
    }
    
    /**
     * Get maximum attempts based on user role and operation
     */
    private function getMaxAttempts($user, Request $request): int
    {
        // Admin users get higher limits
        if ($this->isAdmin($user)) {
            return 100;
        }
        
        // Premium users get higher limits
        if ($this->isPremiumUser($user)) {
            return 50;
        }
        
        // Regular users
        return 20;
    }
    
    /**
     * Log SMS activity for audit trail
     */
    private function logActivity($user, Request $request): void
    {
        // Only log important SMS operations
        $importantOperations = [
            'sms.api.send-test',
            'sms.templates.store',
            'sms.campaigns.store',
            'sms.campaigns.start',
            'sms.autoresponders.store'
        ];
        
        $routeName = $request->route()->getName();
        
        if (in_array($routeName, $importantOperations)) {
            Log::info('SMS operation performed', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'operation' => $routeName,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString()
            ]);
        }
    }
    
    // ==================== Permission Check Methods ====================
    
    /**
     * Check if user can view SMS features
     */
    private function canViewSms($user): bool
    {
        // Implement your logic here
        // Example: check user role, subscription, etc.
        
        // For demo purposes, allow all authenticated users
        return true;
        
        // Example with role-based check:
        // return in_array($user->role, ['admin', 'manager', 'user']);
        
        // Example with permission-based check:
        // return $user->hasPermission('sms.view');
    }
    
    /**
     * Check if user can send SMS
     */
    private function canSendSms($user): bool
    {
        // Check if user has SMS sending privileges
        // You might want to check subscription status, credits, etc.
        
        // For demo purposes, allow all authenticated users
        return true;
        
        // Example with subscription check:
        // return $user->hasActiveSubscription() && $user->sms_credits > 0;
    }
    
    /**
     * Check if user can manage templates
     */
    private function canManageTemplates($user): bool
    {
        // For demo purposes, allow all authenticated users
        return true;
        
        // Example with role check:
        // return in_array($user->role, ['admin', 'manager']);
    }
    
    /**
     * Check if user can manage campaigns
     */
    private function canManageCampaigns($user): bool
    {
        // For demo purposes, allow all authenticated users
        return true;
        
        // Example with role check:
        // return in_array($user->role, ['admin', 'manager']);
    }
    
    /**
     * Check if user can manage autoresponders
     */
    private function canManageAutoresponders($user): bool
    {
        // For demo purposes, allow all authenticated users
        return true;
        
        // Example with role check:
        // return in_array($user->role, ['admin', 'manager']);
    }
    
    /**
     * Check if user can view analytics
     */
    private function canViewAnalytics($user): bool
    {
        // For demo purposes, allow all authenticated users
        return true;
        
        // Example with role check:
        // return in_array($user->role, ['admin', 'manager']);
    }
    
    /**
     * Check if user can manage settings
     */
    private function canManageSettings($user): bool
    {
        // Only admins can manage settings
        return $this->isAdmin($user);
    }
    
    /**
     * Check if user is admin
     */
    private function isAdmin($user): bool
    {
        // Implement your admin check logic
        // return $user->role === 'admin';
        // return $user->hasRole('admin');
        // return $user->is_admin;
        
        // For demo purposes, check if user has admin-like properties
        return isset($user->role) && $user->role === 'admin' ||
               isset($user->is_admin) && $user->is_admin ||
               isset($user->user_type) && $user->user_type === 'admin';
    }
    
    /**
     * Check if user is premium user
     */
    private function isPremiumUser($user): bool
    {
        // Implement your premium user check logic
        // return $user->subscription_type === 'premium';
        // return $user->hasActiveSubscription('premium');
        
        // For demo purposes, return false
        return false;
    }
}