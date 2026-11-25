<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Boundary validation for inbound webhook payloads.
 * The envelope headers (signature/timestamp/idempotency) are validated in the
 * controller security layer; this request validates the JSON body shape.
 */
class IngestWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public ingestion endpoint; auth is via HMAC signature.
    }

    public function rules(): array
    {
        return [
            'payload' => ['sometimes', 'array'],
            'payload.*' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'payload.array' => 'The webhook payload must be a JSON object.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $json = $this->json()->all();
        $this->merge(['payload' => $json ?? []]);
    }
}