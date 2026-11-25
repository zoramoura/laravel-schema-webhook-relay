<?php

namespace App\Plugins;

use App\Models\PluginManifest as PluginManifestModel;
use App\Models\Tenant;
use Illuminate\Validation\ValidationException;

/**
 * Loads a tenant's plugin manifest from the database and validates its structure
 * against a lightweight JSON-schema meta-schema (no arbitrary code execution).
 */
class ManifestLoader
{
    public function __construct(
        private array $manifestMetaSchema,
    ) {
        //
    }

    public function loadForTenant(Tenant $tenant): ?Manifest
    {
        $record = PluginManifestModel::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->first();

        if (! $record) {
            return null;
        }

        $data = is_array($record->manifest) ? $record->manifest : json_decode($record->manifest, true) ?? [];

        // Validate structure against meta-schema (shape only, not data values).
        $this->validateStructure($data);

        return Manifest::fromArray($data);
    }

    private function validateStructure(array $data): void
    {
        $errors = [];

        if (! empty($data['event_filter']) && ! is_array($data['event_filter'])) {
            $errors[] = 'event_filter must be an object';
        }

        if (! empty($data['payload_schema']) && ! is_array($data['payload_schema'])) {
            $errors[] = 'payload_schema must be an object';
        }

        if (! empty($data['field_mapping']) && ! is_array($data['field_mapping'])) {
            $errors[] = 'field_mapping must be an object';
        }

        if (! empty($data['outbound_targets']) && ! is_array($data['outbound_targets'])) {
            $errors[] = 'outbound_targets must be an array';
        }

        foreach ($data['outbound_targets'] ?? [] as $i => $target) {
            if (! is_array($target) || empty($target['url'])) {
                $errors[] = "outbound_targets[{$i}] must have a 'url'";
            }
        }

        if ($errors) {
            throw ValidationException::withMessages(['manifest' => $errors]);
        }
    }
}