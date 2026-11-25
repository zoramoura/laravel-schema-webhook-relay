<?php

namespace App\Plugins;

/**
 * Resolves the list of outbound webhook targets from a manifest.
 */
class OutboundRouter
{
    public function targets(Manifest $manifest): array
    {
        return $manifest->outboundTargets;
    }
}