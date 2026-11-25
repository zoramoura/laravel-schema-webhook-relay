<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('idempotency_key')->nullable()->index();
            $table->string('event_type')->index();
            $table->json('payload');
            $table->json('headers')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('status')->default('received');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'idempotency_key']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};