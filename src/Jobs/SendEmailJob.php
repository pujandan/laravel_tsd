<?php

namespace Daniardev\LaravelTsd\Jobs;

use Daniardev\LaravelTsd\Helpers\AppLog;
use Daniardev\LaravelTsd\Services\EmailInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public int $timeout = 30;

    private array $emailData;

    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;

        $this->onQueue('email');
    }

    public function handle(EmailInterface $emailService): void
    {
        $to = $this->emailData['to'];
        $subject = $this->emailData['subject'];
        $mailable = $this->emailData['mailable'];
        $attachments = $this->emailData['attachments'] ?? [];

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (isset($attachment['s3_path']) && isset($attachment['s3_disk'])) {
                    $content = Storage::disk($attachment['s3_disk'])->get($attachment['s3_path']);
                    $mailable->attachData($content, $attachment['as'], [
                        'mime' => $attachment['mime'] ?? 'application/pdf',
                    ]);
                } elseif (isset($attachment['data'])) {
                    $mailable->attachData($attachment['data'], $attachment['as'], [
                        'mime' => $attachment['mime'] ?? 'application/octet-stream',
                    ]);
                } else {
                    $mailable->attach($attachment['path'], [
                        'as' => $attachment['as'] ?? basename($attachment['path']),
                        'mime' => $attachment['mime'] ?? null,
                    ]);
                }
            }
        }

        $emailService->sendSync($to, $subject, $mailable);

        $mode = config('laravel-tsd.mail.mode', 'mailtrap');
        $service = $mode === 'mailtrap' ? 'Mailtrap API' : 'SMTP';

        Log::channel('json-daily')->info("Email sent successfully via {$service}", AppLog::getJobContext($this->job, [
            'to' => AppLog::maskEmail($to),
            'subject' => $subject,
            'mode' => $mode,
        ]));
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('json-daily')->error('Email job failed after all retries', AppLog::getJobContext($this->job, [
            'to' => AppLog::maskEmail($this->emailData['to'] ?? 'unknown'),
            'subject' => $this->emailData['subject'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]));
    }

    public function tags(): array
    {
        return [
            'email',
            'to:' . AppLog::maskEmail($this->emailData['to'] ?? 'unknown'),
            'subject:' . ($this->emailData['subject'] ?? 'unknown'),
        ];
    }
}
