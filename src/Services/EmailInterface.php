<?php

namespace Daniardev\LaravelTsd\Services;

interface EmailInterface
{
    /**
     * Send email using queue
     *
     * @param string $to
     * @param string $subject
     * @param object $mailable
     * @param array $attachments
     * @param int $priority
     * @return void
     */
    public function send(
        string $to,
        string $subject,
        object $mailable,
        array $attachments = [],
        int $priority = 1
    ): void;

    /**
     * Send email to multiple recipients
     *
     * @param array $tos
     * @param string $subject
     * @param object $mailable
     * @param array $attachments
     * @param int $priority
     * @return void
     */
    public function sendBulk(
        array $tos,
        string $subject,
        object $mailable,
        array $attachments = [],
        int $priority = 1
    ): void;

    /**
     * Send email synchronously (without queue)
     *
     * @param string $to
     * @param string $subject
     * @param object $mailable
     * @param array $attachments
     * @return void
     */
    public function sendSync(
        string $to,
        string $subject,
        object $mailable,
        array $attachments = []
    ): void;

    /**
     * Validate email address format
     *
     * @param string $email
     * @return bool
     */
    public function isValidEmail(string $email): bool;

    /**
     * Send email with retry mechanism
     *
     * @param string $to
     * @param string $subject
     * @param object $mailable
     * @param array $attachments
     * @param int $attempts
     * @return bool
     */
    public function sendWithRetry(
        string $to,
        string $subject,
        object $mailable,
        array $attachments = [],
        int $attempts = 3
    ): bool;
}