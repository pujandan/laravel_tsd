<?php

namespace Daniardev\LaravelTsd\Services;

use Daniardev\LaravelTsd\Exceptions\AppException;
use Daniardev\LaravelTsd\Helpers\AppLog;
use Daniardev\LaravelTsd\Jobs\SendEmailJob;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService implements EmailInterface
{
    private const MAX_RETRY = 3;

    public function send(
        string $to,
        string $subject,
        object $mailable,
        array $attachments = [],
        int $priority = 1
    ): void {
        SendEmailJob::dispatch([
            'to' => $to,
            'subject' => $subject,
            'mailable' => $mailable,
            'attachments' => $attachments,
        ])->onQueue(config('laravel-tsd.mail.queue.name', 'email'))
          ->onConnection(config('laravel-tsd.mail.queue.connection', config('queue.default', 'redis')));

        Log::channel('json-daily')->info('Email queued successfully', AppLog::getRequestContext(null, [
            'to' => AppLog::maskEmail($to),
            'subject' => $subject,
            'priority' => $priority,
        ]));
    }

    public function sendBulk(
        array $tos,
        string $subject,
        object $mailable,
        array $attachments = [],
        int $priority = 1
    ): void {
        foreach ($tos as $to) {
            $this->send($to, $subject, $mailable, $attachments, $priority);
        }

        Log::channel('json-daily')->info('Bulk emails queued', AppLog::getRequestContext(null, [
            'total_recipients' => count($tos),
            'subject' => $subject,
        ]));
    }

    public function sendSync(
        string $to,
        string $subject,
        object $mailable,
        array $attachments = []
    ): void {
        try {
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (isset($attachment['s3_path']) && isset($attachment['s3_disk'])) {
                        $content = \Storage::disk($attachment['s3_disk'])->get($attachment['s3_path']);
                        $mailable->attachData($content, $attachment['as'] ?? basename($attachment['s3_path']), [
                            'mime' => $attachment['mime'] ?? 'application/pdf',
                        ]);
                    } elseif (isset($attachment['path'])) {
                        $mailable->attach($attachment['path'], [
                            'as' => $attachment['as'] ?? basename($attachment['path']),
                            'mime' => $attachment['mime'] ?? null,
                        ]);
                    } elseif (isset($attachment['data'])) {
                        $mailable->attachData($attachment['data'], $attachment['as'], [
                            'mime' => $attachment['mime'] ?? 'application/octet-stream',
                        ]);
                    }
                }
            }

            Mail::to($to)->send($mailable);

            Log::channel('json-daily')->info('Email sent successfully via SMTP', AppLog::getRequestContext(null, [
                'to' => AppLog::maskEmail($to),
                'subject' => $subject,
            ]));

        } catch (Exception $e) {
            Log::channel('json-daily')->error('Failed to send email via SMTP', AppLog::getRequestContext(null, [
                'to' => AppLog::maskEmail($to),
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]));

            throw new AppException('Failed to send email: ' . $e->getMessage(), 500);
        }
    }

    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function sendWithRetry(
        string $to,
        string $subject,
        object $mailable,
        array $attachments = [],
        int $attempts = self::MAX_RETRY
    ): bool {
        $lastException = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $this->sendSync($to, $subject, $mailable, $attachments);
                return true;
            } catch (Exception $e) {
                $lastException = $e;
                if ($i < $attempts - 1) {
                    usleep(pow(2, $i) * 1000000);
                }
            }
        }

        Log::channel('json-daily')->error('All email retry attempts failed', AppLog::getRequestContext(null, [
            'to' => AppLog::maskEmail($to),
            'subject' => $subject,
            'attempts' => $attempts,
            'error' => $lastException?->getMessage(),
        ]));

        return false;
    }
}