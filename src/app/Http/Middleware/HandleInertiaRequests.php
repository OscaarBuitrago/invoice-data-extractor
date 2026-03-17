<?php

namespace App\Http\Middleware;

use App\Models\ClientCompany;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $activeCompanyId = $request->session()->get('active_company_id');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'activeCompany' => $activeCompanyId
                ? ClientCompany::withoutGlobalScopes()->find($activeCompanyId, ['id', 'name', 'tax_id'])
                : null,
            'flash' => [
                'import_result' => fn () => $request->session()->get('import_result'),
            ],
        ];
    }
}
