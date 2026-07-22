<?php

namespace Daniardev\LaravelTsd\Services;

use Daniardev\LaravelTsd\Exceptions\AppException;
use Daniardev\LaravelTsd\Helpers\AppLog;
use Daniardev\LaravelTsd\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Log;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;

class MailtrapService implements EmailInterface
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('laravel-tsd.mail.mailtrap.api_key');

        if (!$this->apiKey) {
            throw new AppException('Mailtrap API key is not configured', 500);
        }
    }

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
            $html = $mailable->render();

            $fromAddress = config('mail.from.address');
            $fromName = config('mail.from.name', config('app.name'));

            $email = (new MailtrapEmail())
                ->from(new Address($fromAddress, $fromName))
                ->to(new Address($to))
                ->subject($subject)
                ->html($html);

            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (isset($attachment['s3_path']) && isset($attachment['s3_disk'])) {
                        $content = \Storage::disk($attachment['s3_disk'])->get($attachment['s3_path']);
                        $email->attach($content, $attachment['as'] ?? basename($attachment['s3_path']), $attachment['mime'] ?? 'application/pdf');
                    } elseif (isset($attachment['path'])) {
                        $content = file_get_contents($attachment['path']);
                        $email->attach($content, $attachment['as'] ?? basename($attachment['path']), $attachment['mime'] ?? 'application/octet-stream');
                    } elseif (isset($attachment['data'])) {
                        $email->attach($attachment['data'], $attachment['as'], $attachment['mime'] ?? 'application/octet-stream');
                    }
                }
            }

            $mailtrap = MailtrapClient::initSendingEmails(apiKey: $this->apiKey);
            $response = $mailtrap->send($email);

            Log::channel('json-daily')->info('Email sent successfully via Mailtrap', AppLog::getRequestContext(null, [
                'to' => AppLog::maskEmail($to),
                'subject' => $subject,
                'status' => $response->getStatusCode(),
            ]));

        } catch (\Exception $e) {
            Log::channel('json-daily')->error('Failed to send email via Mailtrap', AppLog::getRequestContext(null, [
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
        int $attempts = 3
    ): bool {
        $lastException = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $this->sendSync($to, $subject, $mailable, $attachments);
                return true;
            } catch (\Exception $e) {
                $lastException = $e;
                if ($i < $attempts - 1) {
                    usleep(pow(2, $i) * 1000000);
                }
            }
        }

        Log::channel('json-daily')->error('Email sending failed after retries', AppLog::getRequestContext(null, [
            'to' => AppLog::maskEmail($to),
            'subject' => $subject,
            'attempts' => $attempts,
            'error' => $lastException?->getMessage(),
        ]));

        return false;
    }
}