<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_event_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('target_url');
            $table->string('method')->default('POST');
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->json('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->boolean('succeeded')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'succeeded']);
            $table->index(['webhook_event_id', 'attempt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_logs');
    }
};