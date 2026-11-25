<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dead_letter_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_event_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('target_url');
            $table->string('method')->default('POST');
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->text('last_error');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('failed_at')->useCurrent();
            $table->timestamp('replayed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'failed_at']);
            $table->index(['tenant_id', 'replayed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dead_letter_deliveries');
    }
};