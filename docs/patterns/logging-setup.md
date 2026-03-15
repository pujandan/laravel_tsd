# Logging Setup Guide

This guide explains how to properly configure logging for the Laravel TSD package to work correctly.

## Overview

The Laravel TSD package uses a **`json-daily`** logging channel for:
- **AppHandler** - Exception logging (401/403 security issues, 500+ system errors)
- **AppSafe** - Silent execution failures (emails, external APIs, webhooks)

Without proper configuration, these features will **not log anything**.

---

## Required Configuration

### Step 1: Configure Logging Channel

**No custom files needed!** The package provides `AppLogFormatJson` class.

Update `config/logging.php`:

```php
return [
    // ... default configuration

    'channels' => [

        // ... other channels (stack, single, daily, etc.)

        // Add this channel for Laravel TSD package
        'json-daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'tap' => [Daniardev\LaravelTsd\Logging\AppLogFormatJson::class],
        ],

    ],
];
```

**Key Features of `AppLogFormatJson` from package:**
- ✅ `datetime` always at the top (easier to scan logs)
- ✅ Pretty print for local/dev/staging (easier to read)
- ✅ Compact JSON for production (smaller file size)

### Step 2: Update bootstrap/app.php (Laravel 11/12)

**Super simple!** Just add one line:

```php
use Daniardev\LaravelTsd\Exceptions\AppHandler;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(/* ... */)
    ->withMiddleware(/* ... */)
    ->withExceptions(fn (\Illuminate\Foundation\Configuration\Exceptions $e) => AppHandler::configure($e))
    ->create();
```

That's it! The `AppHandler::configure()` method automatically:
- Registers all exception reporting callbacks (logging)
- Overrides exception rendering (JSON responses)

**For Laravel 8-10:** The handler works automatically when your `app/Exceptions/Handler` extends `AppHandler`.

### Step 3: Update Environment Variables

### Step 3: Clear Configuration Cache

Update `.env` file:

```env
# Set default channel to json-daily
LOG_CHANNEL=json-daily

# Set minimum log level (debug, info, notice, warning, error, critical, alert, emergency)
LOG_LEVEL=debug
```

### Step 4: Clear Configuration Cache

```bash
php artisan config:clear
php artisan cache:clear
```

---

## Verification

### Test Logging Manually

```bash
# Open tinker
php artisan tinker

# Test logging
>>> app('log')->channel('json-daily')->info('Test log', ['test' => 'data']);
=> true

# Exit tinker
>>> exit
```

### Check Log File

```bash
# View today's log file
cat storage/logs/laravel-$(date +%Y-%m-%d).log

# Or use tail for live monitoring
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log
```

**Expected output (JSON format):**

*Development/Local (Pretty Print):*
```json
{
    "datetime": "2025-01-15T10:30:45.123456+00:00",
    "message": "Test log",
    "context": {
        "test": "data"
    },
    "level": 200,
    "level_name": "INFO",
    "channel": "json-daily",
    "extra": {}
}
```

*Production (Compact):*
```json
{"datetime":"2025-01-15T10:30:45.123456+00:00","message":"Test log","context":{"test":"data"},"level":200,"level_name":"INFO","channel":"json-daily","extra":{}}
```

### Test AppHandler Logging

```bash
# Trigger a 401 error (should log as WARNING)
curl -i http://your-app.test/api/protected-route

# Check for WARNING log
cat storage/logs/laravel-$(date +%Y-%m-%d).log | grep WARNING
```

**Expected output:**
```json
{
    "datetime": "2025-01-15T10:30:45.123456+00:00",
    "message": "API security issue detected",
    "context": {
        "request_id": "...",
        "user_id": null,
        "ip": "...",
        "exception_type": "AuthenticationException",
        "message": "Unauthenticated"
    },
    "level": 300,
    "level_name": "WARNING",
    "channel": "json-daily",
    "extra": {}
}
```

---

## What Gets Logged

### AppHandler Exception Logging

| Exception Type | HTTP Code | Log Level | Logged? | Reason |
|----------------|-----------|-----------|---------|--------|
| `AuthenticationException` | 401 | WARNING | ✅ Yes | Security monitoring (web + API) |
| `AuthorizationException` | 403 | WARNING | ✅ Yes | Security monitoring (web + API) |
| `QueryException` | 500 | ERROR | ✅ Yes | Database error (web + API) |
| `Exception` | 500 | ERROR | ✅ Yes | System error (web + API) |
| `NotFoundHttpException` | 404 | - | ❌ No | Normal user error |
| `ValidationException` | 422 | - | ❌ No | Expected validation |
| `ThrottleRequestsException` | 429 | - | ❌ No | Rate limiting working |

**Important:** Both web and API requests are now logged for debugging purposes.

### AppSafe Silent Execution Logging

| Operation | Log Level | Logged? |
|-----------|-----------|---------|
| Email failures | WARNING | ✅ Yes |
| External API failures | WARNING | ✅ Yes |
| Webhook failures | WARNING | ✅ Yes |
| All silent failures | WARNING | ✅ Yes |

---

## Troubleshooting

### Problem: Logs not appearing in file

**Symptoms:**
- No log entries in `storage/logs/laravel-{date}.log`
- File doesn't exist or is empty

**Solutions:**

1. **Check `config/logging.php`:**
   ```bash
   php artisan config:list | grep LOG_CHANNEL
   ```
   Expected output: `LOG_CHANNEL = json-daily`

2. **Verify channel exists:**
   ```bash
   php artisan tinker
   >>> config('logging.channels.json-daily');
   ```
   Should return array with channel configuration.

3. **Clear config cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Check file permissions:**
   ```bash
   ls -la storage/logs/
   # Should be writable by web server user
   chmod -R 775 storage/logs/
   ```

5. **Test with absolute path in config:**
   ```php
   'json-daily' => [
       'path' => storage_path('logs/laravel.log'),
       // Or use absolute path
       // 'path' => '/var/www/html/storage/logs/laravel.log',
   ],
   ```

### Problem: Logs not in JSON format

**Symptoms:**
- Logs appear as plain text instead of JSON
- Format: `[2025-01-15 10:30:45] local.INFO: Test log {"test":"data"}`

**Solutions:**

1. **Verify package is installed:**
   ```bash
   composer show daniardev/laravel-tsd
   ```

2. **Verify `tap` configuration uses package class:**
   ```bash
   php artisan tinker
   >>> config('logging.channels.json-daily.tap');
   ```
   Expected: `[0 => "Daniardev\\LaravelTsd\\Logging\\AppLogFormatJson"]`

3. **Clear all caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan optimize:clear
   ```

4. **Verify class exists in package:**
   ```bash
   ls -la vendor/daniardev/laravel-tsd/src/Logging/AppLogFormatJson.php
   ```

### Problem: AppHandler not logging 401/403

**Symptoms:**
- API returns correct error response
- No log entry in file

**Solutions:**

1. **Verify Handler extends AppHandler:**
   ```php
   // app/Exceptions/Handler.php
   class Handler extends \Daniardev\LaravelTsd\Exceptions\AppHandler
   {
       // ...
   }
   ```

2. **Check request expects JSON:**
   ```bash
   # Add Accept header to test
   curl -H "Accept: application/json" http://your-app.test/api/protected-route
   ```

3. **Test with explicit API route:**
   ```php
   // routes/api.php
   Route::get('/test-auth', function () {
       throw new \Illuminate\Auth\AuthenticationException();
   });
   ```

4. **Verify log level:**
   ```bash
   # .env file
   LOG_LEVEL=debug  # Must be debug or info to capture WARNING
   ```

### Problem: Log file too large

**Solutions:**

1. **Reduce retention days:**
   ```php
   'json-daily' => [
       'days' => 7,  // Keep only 7 days
   ],
   ```

2. **Set minimum log level:**
   ```env
   LOG_LEVEL=warning  # Only log WARNING and above
   ```

3. **Set up log rotation:**
   ```bash
   # Add to crontab
   0 0 * * * find /path/to/storage/logs -name "laravel-*.log" -mtime +14 -delete
   ```

---

## Advanced Configuration

### Separate Security Log File

To log security issues (401/403) to a separate file:

```php
// config/logging.php

'channels' => [
    'json-daily' => [
        // System errors (500+)
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'error',
        'days' => 14,
        'tap' => [Daniardev\LaravelTsd\Logging\AppLogFormatJson::class],
    ],

    'security' => [
        // Security issues (401/403)
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 30,  // Keep longer for audit
        'tap' => [Daniardev\LaravelTsd\Logging\AppLogFormatJson::class],
    ],
],
```

Then modify `AppHandler` to use appropriate channel based on exception type.

### Custom Log Context

To add additional context to all logs, create a custom formatter in your project:

```php
// app/Logging/AppLogFormatJson.php

namespace App\Logging;

use Daniardev\LaravelTsd\Logging\AppLogFormatJson as BaseFormatJsonLog;
use Illuminate\Support\Facades\Auth;

class AppLogFormatJson extends BaseFormatJsonLog
{
    public function __invoke($logger): void
    {
        // Call parent to set up formatter
        parent::__invoke($logger);

        // Add custom context to all logs
        $logger->pushProcessor(function ($record) {
            $record['extra']['app_name'] = config('app.name');
            $record['extra']['app_env'] = config('app.env');
            $record['extra']['server_hostname'] = gethostname();

        if (Auth::check()) {
            $record['extra']['user'] = [
                'id' => Auth::id(),
                'email' => Auth::user()->email,
            ];
        }

        return $record;
    });
}
```

---

## Log Monitoring

### View Logs in Real-Time

```bash
# All logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log

# Only errors
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep ERROR

# Only security issues
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep WARNING

# Pretty print JSON
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | jq
```

### Search Logs

```bash
# Search by user ID
grep "user_id\":123" storage/logs/laravel-*.log

# Search by request ID
grep "request_id\":\"abc-123" storage/logs/laravel-*.log

# Search by exception type
grep "QueryException" storage/logs/laravel-*.log

# Count errors by type
grep "ERROR" storage/logs/laravel-*.log | jq -r '.context.exception_type' | sort | uniq -c
```

### Log Analysis Tools

Consider using:
- **Laravel Log Viewer** package for web UI
- **ELK Stack** (Elasticsearch, Logstash, Kibana) for large applications
- **Grafana Loki** for log aggregation
- **Sentry** for error tracking

---

## Summary

| Step | Action | File |
|------|--------|------|
| 1 | Add `json-daily` channel | `config/logging.php` |
| 2 | Set `LOG_CHANNEL=json-daily` | `.env` |
| 3 | Clear config cache | `php artisan config:clear` |
| 4 | Verify with test log | `php artisan tinker` |

---

**Last Updated:** 2025-01-15