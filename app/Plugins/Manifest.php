<?php

namespace App\Plugins;

/**
 * Immutable value object representing a parsed, validated plugin manifest.
 * All fields are optional except the manifest must be a decodable array.
 */
class Manifest
{
    public function __construct(
        public readonly array $raw,
        public readonly ?string $name,
        public readonly array $eventFilter,
        public readonly array $payloadSchema,
        public readonly array $fieldMapping,
        public readonly array $outboundTargets,
        public readonly bool $isActive,
    ) {
        //
    }

    public static function fromArray(array $data): self
    {
        return new self(
            raw: $data,
            name: $data['name'] ?? null,
            eventFilter: $data['event_filter'] ?? [],
            payloadSchema: $data['payload_schema'] ?? [],
            fieldMapping: $data['field_mapping'] ?? [],
            outboundTargets: $data['outbound_targets'] ?? [],
            isActive: $data['is_active'] ?? true,
        );
    }

    public function hasEventFilter(): bool
    {
        return ! empty($this->eventFilter);
    }

    public function hasPayloadSchema(): bool
    {
        return ! empty($this->payloadSchema);
    }

    public function hasFieldMapping(): bool
    {
        return ! empty($this->fieldMapping);
    }

    public function hasOutboundTargets(): bool
    {
        return ! empty($this->outboundTargets);
    }
}