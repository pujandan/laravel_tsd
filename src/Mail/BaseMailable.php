<?php

namespace Daniardev\LaravelTsd\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

abstract class BaseMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected array $data = []
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->getSubject(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->getViewName(),
            with: $this->getData(),
        );
    }

    public function attachments(): array
    {
        return [];
    }

    abstract protected function getSubject(): string;

    abstract protected function getViewName(): string;

    protected function getData(): array
    {
        return $this->data;
    }

    public function setData(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function getDataValue(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}
