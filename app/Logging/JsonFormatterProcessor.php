<?php

namespace App\Logging;

use Monolog\Processor\ProcessorInterface;

/**
 * Injects structured, machine-readable context into every log record.
 * Produces JSON-log friendly fields (service, env, request_id) so log
 * aggregators can index them without regex parsing.
 */
class JsonFormatterProcessor implements ProcessorInterface
{
    public function __invoke(array $record): array
    {
        $record['extra'] = array_merge($record['extra'] ?? [], [
            'service' => 'webhook-relay',
            'env' => config('app.env'),
            'request_id' => request()->header('X-Request-Id', request()->id()),
        ]);

        return $record;
    }
}