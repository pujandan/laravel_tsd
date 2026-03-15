<?php

namespace Daniardev\LaravelTsd\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

/**
 * AppLog - Centralized logging utilities
 *
 * Provides reusable helper methods for consistent logging format
 * across Handler.php, AppSafe.php, and other components.
 *
 * @package Daniardev\LaravelTsd\Helpers
 */
class AppLog
{
    /**
     * Get user context for logging
     *
     * Returns consistent user information structure for logs.
     * Used by Handler.php and AppSafe.php
     *
     * @param Request|null $request Current request (optional)
     * @return array User information with authenticated status
     *
     * @example
     * $context['user'] = AppLog::getUserContext($request);
     * // Returns: ['authenticated' => bool, 'user_id' => int|null, 'email' => string]
     */
    public static function getUserContext(?Request $request): array
    {
        // Try from request first, fallback to Auth facade
        $user = $request ? $request->user() : Auth::user();

        if (!$user) {
            return [
                'authenticated' => false,
                'user_id' => null,
            ];
        }

        return [
            'authenticated' => true,
            'user_id' => $user->id,
            'email' => self::maskEmail($user->email ?? null),
        ];
    }

    /**
     * Mask email for logging (keep first 3 chars, mask the rest)
     *
     * Protects user privacy while keeping log searchable.
     * Example: "john.doe@example.com" → "joh***@***"
     *
     * @param string|null $email Email to mask
     * @return string Masked email
     *
     * @example
     * AppLog::maskEmail('john.doe@example.com'); // "joh***@***"
     * AppLog::maskEmail(null); // "N/A"
     */
    public static function maskEmail(?string $email): string
    {
        if (!$email) {
            return 'N/A';
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***';
        }

        $username = substr($parts[0], 0, 3) . '***';
        $domain = '***';

        return $username . '@' . $domain;
    }

    /**
     * mask phone number for logging (keep first 4 chars, mask the rest)
     *
     * Protects user privacy while keeping log searchable.
     * Example: "081234567890" → "0812*****7890"
     *
     * @param string|null $phoneNumber Phone number to mask
     * @return string Masked phone number
     *
     * @example
     * AppLog::maskPhoneNumber('081234567890'); // "0812*****7890"
     * AppLog::maskPhoneNumber('0822260260'); // "0822****0260"
     * AppLog::maskPhoneNumber(null); // "N/A"
     */
    public static function maskPhoneNumber(?string $phoneNumber): string
    {
        if (!$phoneNumber) {
            return 'N/A';
        }

        $length = strlen($phoneNumber);
        if ($length <= 6) {
            return '****';
        }

        // Keep first 4 and last 4 characters, mask the middle
        $start = substr($phoneNumber, 0, 4);
        $end = substr($phoneNumber, -4);
        $maskedLength = $length - 8;
        $masked = str_repeat('*', max(3, $maskedLength));

        return $start . $masked . $end;
    }

    /**
     * Sanitize URL to remove sensitive query parameters
     *
     * Removes passwords, tokens, and other sensitive data from URLs
     * before logging to prevent security leaks.
     *
     * @param string $url URL to sanitize
     * @return string Sanitized URL
     *
     * @example
     * $url = 'https://example.com?api_token=secret123&user=1';
     * AppLog::sanitizeUrl($url); // "https://example.com?api_token=******&user=1"
     */
    public static function sanitizeUrl(string $url): string
    {
        // Remove common sensitive query parameters
        $sensitiveParams = ['api_token', 'access_token', 'password', 'secret'];

        foreach ($sensitiveParams as $param) {
            $url = preg_replace('/([?&]' . $param . '=)[^&]*/i', '$1******', $url);
        }

        return $url;
    }

    /**
     * Sanitize stack trace by filtering sensitive data
     *
     * Removes passwords, tokens, API keys from stack traces before logging.
     * Used by Handler.php and AppSafe.php.
     *
     * @param string|null $trace Stack trace to sanitize
     * @return string|null Sanitized trace
     *
     * @example
     * AppLog::sanitizeTrace($exception->getTraceAsString());
     */
    public static function sanitizeTrace(?string $trace): ?string
    {
        if ($trace === null) {
            return null;
        }

        // Remove potential sensitive data from trace
        $sanitized = preg_replace('/(password|token|api_key|secret|private_key)\s*=>\s*[^\s,\]]+/i', '$1=>******', $trace);
        $sanitized = preg_replace('/(Bearer\s+)[^\s\'"]+/', '$1******', $sanitized);
        return preg_replace('/(authorization:\s*[^\s\'"]+)/i', 'authorization:******', $sanitized);
    }

    /**
     * Get job context for queue job logging
     *
     * Returns consistent job information structure for logs.
     * Used by Jobs (SendEmailJob, SendWhatsAppJob, etc.) for consistent logging format.
     *
     * Note: Uses maxTries property (not getTries() method) for compatibility with all job types (Redis, Database, etc.)
     *
     * @param mixed $job Job instance from Laravel Queue
     * @param array $extraContext Additional context to merge (optional)
     * @return array Job information with queue details
     *
     * @example
     * $context = array_merge(
     *     AppLog::getJobContext($this->job),
     *     ['to' => $email, 'subject' => $subject]
     * );
     * Log::channel('json-daily')->info('Email sent', $context);
     */
    public static function getJobContext($job, array $extraContext = []): array
    {
        return array_merge([
            'job_id' => $job?->getJobId() ?? 'N/A',
            'queue' => $job?->getQueue() ?? 'default',
            'connection' => $job?->getConnectionName() ?? 'default',
            'attempts' => $job?->attempts() ?? 0,
            'max_tries' => $job?->maxTries ?? 0, // Use property (not method) for RedisJob compatibility
        ], $extraContext);
    }

    /**
     * Get request context for audit logging
     *
     * Returns consistent request information structure for logs.
     * Used by Services (EmailService, WhatsappService, etc.) for consistent audit trail.
     *
     * @param Request|null $request Current request (optional, uses global request() if null)
     * @param array $extraContext Additional context to merge (optional)
     * @return array Request information with user details
     *
     * @example
     * Log::channel('json-daily')->info('Email sent successfully', AppLog::getRequestContext(
     *     request(),
     *     ['to' => AppLog::maskEmail($to), 'subject' => $subject]
     * ));
     */
    public static function getRequestContext(?Request $request = null, array $extraContext = []): array
    {
        $request = $request ?: request();

        return array_merge([
            'request_id' => $request?->attributes->get('request_id', 'N/A') ?? 'N/A',
            'request' => [
                'method' => $request?->method() ?? 'N/A',
                'url' => $request ? self::sanitizeUrl($request->fullUrl()) : 'N/A',
                'ip' => $request?->ip() ?? 'N/A',
                'user_agent' => $request?->userAgent() ?? 'N/A',
            ],
            'user' => $request ? self::getUserContext($request) : [
                'authenticated' => false,
                'user_id' => null,
            ],
        ], $extraContext);
    }
}
