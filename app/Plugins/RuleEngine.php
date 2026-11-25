<?php

namespace App\Plugins;

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

/**
 * Rule evaluation engine: event filtering + JSON-schema payload validation.
 * Declarative and deterministic — never executes user-supplied code.
 */
class RuleEngine
{
    public function matchesEventFilter(Manifest $manifest, string $eventType): bool
    {
        if (! $manifest->hasEventFilter()) {
            return true; // No filter = accept all events.
        }

        $filter = $manifest->eventFilter;

        // Match by event type.
        if (isset($filter['type'])) {
            $expected = is_array($filter['type']) ? $filter['type'] : [$filter['type']];
            if (! in_array($eventType, $expected, true)) {
                return false;
            }
        }

        return true;
    }

    public function validatePayload(Manifest $manifest, array $payload): array
    {
        if (! $manifest->hasPayloadSchema()) {
            return $payload; // No schema = accept as-is.
        }

        $validator = new Validator();
        $validator->validate($payload, (object) $manifest->payloadSchema);

        if (! $validator->isValid(Constraint::CHECK_MODE_CORE)) {
            $messages = [];
            foreach ($validator->getErrors() as $error) {
                $messages[] = $error['message'] ?? 'Invalid payload';
            }

            throw new \Illuminate\Validation\ValidationException(
                \Illuminate\Support\MessageBag::make(['payload' => $messages])
            );
        }

        return $payload;
    }
}