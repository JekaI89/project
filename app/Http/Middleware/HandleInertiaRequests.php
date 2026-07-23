<?php

namespace App\Http\Middleware;

use App\Domain\Auth\PermissionService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $permissions = app(PermissionService::class);

        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'locale' => app()->getLocale(),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'login' => $user->login,
                    'email' => $user->email,
                    'podr_id' => $user->podr_id,
                    'podr_name' => $user->podr_name,
                    'is_admin' => $user->is_admin,
                    'product_user_id' => $user->product_user_id,
                ] : null,
            ],
            'modules' => $user ? $permissions->modulesFor($user) : [],
            'top_menu' => $user ? $permissions->topMenuFor($user) : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ];
    }
}
