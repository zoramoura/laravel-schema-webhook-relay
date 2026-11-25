<?php

namespace Database\Seeders;

use App\Models\PluginManifest;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Sample tenant with a known HMAC secret for local development/testing.
        $tenant = Tenant::create([
            'slug' => 'acme',
            'name' => 'Acme Corporation',
            'hmac_secret' => 'acme_dev_secret_change_me',
            'is_active' => true,
        ]);

        // Sample declarative plugin manifest for the Acme tenant.
        PluginManifest::create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'manifest' => [
                'name' => 'acme-invoice-routing',
                'event_filter' => [
                    'type' => 'invoice.payment_succeeded',
                ],
                'payload_schema' => [
                    'type' => 'object',
                    'required' => ['id', 'amount', 'currency', 'status'],
                    'properties' => [
                        'id' => ['type' => 'string'],
                        'amount' => ['type' => 'number'],
                        'currency' => ['type' => 'string'],
                        'status' => ['type' => 'string'],
                    ],
                ],
                'field_mapping' => [
                    'invoice_id' => '$.id',
                    'amount_cents' => '$.amount',
                    'currency' => '$.currency',
                    'status' => '$.status',
                ],
                'outbound_targets' => [
                    [
                        'url' => 'https://example.local/webhook/acme-invoices',
                        'method' => 'POST',
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                    ],
                ],
            ],
        ]);
    }
}