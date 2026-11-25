<?php

namespace App\Plugins;

/**
 * Deterministic field-mapping pipeline. Resolves dot-notation / JSON-pointer
 * style source paths (e.g. "$.id") from the payload and emits a transformed
 * array suitable for outbound delivery.
 */
class TransformationPipeline
{
    public function transform(Manifest $manifest, array $payload): array
    {
        if (! $manifest->hasFieldMapping()) {
            return $payload; // No mapping = pass through unchanged.
        }

        $mapped = [];

        foreach ($manifest->fieldMapping as $targetField => $sourcePath) {
            $value = $this->resolvePath($payload, $sourcePath);
            if ($value !== null) {
                $mapped[$targetField] = $value;
            }
        }

        return $mapped;
    }

    private function resolvePath(array $data, string $path): mixed
    {
        // Support JSON-pointer style: "$.id", "$.a.b", or plain "id".
        $path = ltrim($path, '$.');
        if ($path === '') {
            return null;
        }

        $segments = explode('.', $path);
        $current = $data;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }
}