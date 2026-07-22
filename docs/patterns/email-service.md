# Email Service Pattern

Email service dengan queue, retry mechanism, dan multi-driver support (SMTP/Mailtrap).

## Setup

### 1. Publish Config

```bash
php artisan vendor:publish --tag="laravel-tsd-config"
```

### 2. Environment Variables

```env
# Mail mode: mailtrap atau smtp
MAILER_MODE=mailtrap

# Mailtrap (jika mode=mailtrap)
MAILTRAP_API_KEY=your_api_key
MAILTRAP_USE_SANDBOX=true
MAILTRAP_INBOX_ID=your_inbox_id

# Queue (default Laravel)
QUEUE_CONNECTION=redis
MAILER_QUEUE_NAME=email

# Retry
MAILER_MAX_ATTEMPTS=3
MAILER_TIMEOUT=30

# Mail from config (Laravel default)
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME=App Name
```

## Interface

```php
interface EmailInterface
{
    public function send(string $to, string $subject, object $mailable, array $attachments = [], int $priority = 1): void;
    public function sendBulk(array $tos, string $subject, object $mailable, array $attachments = [], int $priority = 1): void;
    public function sendSync(string $to, string $subject, object $mailable, array $attachments = []): void;
    public function isValidEmail(string $email): bool;
    public function sendWithRetry(string $to, string $subject, object $mailable, array $attachments = [], int $attempts = 3): bool;
}
```

## Usage

### Controller

```php
class UserController extends Controller
{
    public function __construct(private EmailInterface $emailService) {}

    public function sendWelcome(UserFormRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $user = $this->service->create($request->validated());

            $this->emailService->send(
                to: $user->email,
                subject: 'Welcome to ' . config('app.name'),
                mailable: new WelcomeEmail($user)
            );

            return AppResponse::success(UserResource::make($user), __('User created'));
        });
    }
}
```

### Mailable

```php
use Daniardev\LaravelTsd\Mail\BaseMailable;

class WelcomeEmail extends BaseMailable
{
    public function __construct(public User $user) {}

    public function build(): self
    {
        return $this
            ->subject('Welcome')
            ->view('emails.welcome');
    }
}
```

### With Attachments

```php
$this->emailService->send(
    to: $user->email,
    subject: 'Invoice',
    mailable: new InvoiceEmail($order),
    attachments: [
        // From S3/MinIO
        ['s3_path' => 'invoices/inv-123.pdf', 's3_disk' => 'files', 'as' => 'invoice.pdf', 'mime' => 'application/pdf'],
        // From local path
        ['path' => '/tmp/report.pdf', 'as' => 'report.pdf', 'mime' => 'application/pdf'],
        // Raw data
        ['data' => $pdfContent, 'as' => 'document.pdf', 'mime' => 'application/pdf'],
    ]
);
```

### Bulk Send

```php
$recipients = User::pluck('email')->toArray();

$this->emailService->sendBulk(
    tos: $recipients,
    subject: 'Announcement',
    mailable: new AnnouncementEmail($announcement)
);
```

### Send Sync (tanpa queue)

```php
$this->emailService->sendSync(
    to: $user->email,
    subject: 'Verification Code',
    mailable: new VerificationEmail($code)
);
```

### Send with Retry

```php
$success = $this->emailService->sendWithRetry(
    to: $user->email,
    subject: 'Important Notice',
    mailable: new NoticeEmail($notice),
    attempts: 5
);

if (!$success) {
    // Handle failure
}
```

## Service Registration

```php
// app/Providers/AppServiceProvider.php

use Daniardev\LaravelTsd\Services\EmailInterface;
use Daniardev\LaravelTsd\Services\EmailService;
// atau
use Daniardev\LaravelTsd\Services\MailtrapService;

public function register(): void
{
    $emailService = config('laravel-tsd.mail.mode') === 'mailtrap'
        ? MailtrapService::class
        : EmailService::class;

    $this->app->bind(EmailInterface::class, $emailService);
}
```

## Drivers

| Mode | Class | Description |
|------|-------|-------------|
| `mailtrap` | `MailtrapService` | Mailtrap API (production recommended) |
| `smtp` | `EmailService` | Laravel default SMTP driver |

## Queue Job

Email dikirim via queue job `SendEmailJob`:
- Queue name: `email` (configurable)
- Connection: `redis` (configurable)
- Retry with exponential backoff: [10, 30, 60] seconds
- Timeout: 30 seconds

## Logging

Semua aktivitas email di-log ke `json-daily` channel:
- Queued: INFO with masked email
- Sent: INFO with status
- Failed: ERROR with masked email & error message