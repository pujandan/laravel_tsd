<?php

namespace Daniardev\LaravelTsd\Exceptions;

use Exception;
use JetBrains\PhpStorm\NoReturn;

class AppException extends Exception
{
    protected ?string $customMessage = null;
    protected int $statusCode;

    public function __construct(?string $message = null, int $code = 404, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->customMessage = $message;
        $this->statusCode = $code;
    }

    public function message(): string
    {
        return $this->customMessage ?? $this->getMessage();
    }

    public function code(): int
    {
        return $this->statusCode;
    }

    /**
     * Dump data as JSON response and exit.
     *
     * @param mixed $data Data to dump
     * @param int $status HTTP status code
     * @return void
     */
    #[NoReturn]
    public static function dd(mixed $data, int $status = 200): void
    {
        response()
            ->json($data, $status)
            ->send();

        exit;
    }
}
