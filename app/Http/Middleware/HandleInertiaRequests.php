<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $company = null;
        $mobileNavbarConfig = null;
        $debug = null;

        try {
            $company = \App\Models\Company::query()
                ->select('name', 'logo', 'updated_at')
                ->first();
        } catch (\Throwable $e) {
            $company = null;
        }

        try {
            $mobileNavbarConfig = AppSetting::get('mobile_navbar_config', null);
        } catch (\Throwable $e) {
            $mobileNavbarConfig = null;
        }

        // #region debug-point mobile-navbar-blank-share
        try {
            $envPath = base_path('.dbg/mobile-navbar-blank.env');
            if (is_file($envPath)) {
                $lines = preg_split("/\r\n|\n|\r/", (string) file_get_contents($envPath));
                $data = [];
                foreach ($lines as $line) {
                    $line = trim((string) $line);
                    if ($line === '' || !str_contains($line, '=')) {
                        continue;
                    }
                    [$k, $v] = explode('=', $line, 2);
                    $data[trim($k)] = trim($v);
                }

                if (!empty($data['DEBUG_SERVER_URL']) && !empty($data['DEBUG_SESSION_ID'])) {
                    $debug = [
                        'server_url' => $data['DEBUG_SERVER_URL'],
                        'session_id' => $data['DEBUG_SESSION_ID'],
                    ];
                }
            }
        } catch (\Throwable $e) {
            $debug = null;
        }
        // #endregion debug-point mobile-navbar-blank-share

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
                'roles' => $user ? $user->getRoleNames() : [],
                'unreadNotificationsCount' => $user ? $user->unreadNotifications()->count() : 0,
                'recentNotifications' => $user ? $user->notifications()->latest()->limit(5)->get() : [],
            ],
            'company' => $company
                ? [
                    'name' => $company->name ?? 'USICS ERP',
                    'logo' => $company->logo ?? '/images/usics.png',
                ]
                : [
                    'name' => 'USICS ERP',
                    'logo' => '/images/usics.png',
                ],
            'csrf_token' => csrf_token(),
            'settings' => [
                'mobile_navbar' => $mobileNavbarConfig,
            ],
            'debug' => $debug,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
