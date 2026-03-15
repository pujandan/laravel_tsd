<?php

namespace Daniardev\LaravelTsd\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\LogRecord;

/**
 * JSON Log Formatter with datetime at top and pretty print for non-production
 *
 * Usage in config/logging.php:
 * 'json-daily' => [
 *     'tap' => [Daniardev\LaravelTsd\Logging\AppLogFormatJson::class],
 * ],
 */
class AppLogFormatJson
{
    /**
     * Customize the given logger instance.
     *
     * Features:
     * - datetime at the top (for better readability)
     * - pretty print for non-production (for easier debugging)
     * - compact JSON for production (smaller file size)
     */
    public function __invoke($logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                $formatter = new class extends JsonFormatter {
                    /**
                     * {@inheritdoc}
                     */
                    public function format(LogRecord $record): string
                    {
                        // Convert LogRecord to array
                        $recordArray = $record->toArray();

                        // Move datetime to the top
                        $formatted = [
                            'datetime' => $recordArray['datetime'],
                            'message' => $recordArray['message'],
                            'context' => $recordArray['context'],
                            'level' => $recordArray['level'],
                            'level_name' => $recordArray['level_name'],
                            'channel' => $recordArray['channel'],
                            'extra' => $recordArray['extra'],
                        ];

                        // Normalize data
                        $normalized = $this->normalize($formatted);

                        // Pretty print for non-production, compact for production
                        $isProduction = config('app.env') === 'production';
                        $json = $isProduction
                            ? json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                            : json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                        return $json . "\n";
                    }
                };

                $handler->setFormatter($formatter);
            }
        }
    }
}