<?php

namespace App\Http\Middleware;

use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @see https://inertiajs.com/a-server-side-view#setup
     */
    protected $rootView = 'app';

    /**
     * Determines the current session version.
     *
     * @see https://inertiajs.com/versioning
     */
    public function version($request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default across all Inertia responses.
     *
     * @return array<string, mixed>
     */
    public function share($request): array
    {
        return [
            'appName' => config('app.name', 'Webhook Relay'),
            'env' => config('app.env'),
        ];
    }
}