<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Context;

use App\Actions\ClientCompanies\SelectClientCompanyAction;
use App\Http\Controllers\Controller;
use App\Models\ClientCompany;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyContextController extends Controller
{
    public function select(): Response
    {
        $companies = ClientCompany::orderBy('name')->get(['id', 'name', 'tax_id']);

        return Inertia::render('Context/SelectCompany', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request, SelectClientCompanyAction $action): RedirectResponse
    {
        $request->validate([
            'client_company_id' => ['required', 'ulid'],
        ]);

        try {
            $action->handle($request->string('client_company_id')->toString());
        } catch (AuthorizationException) {
            abort(403);
        }

        return redirect()->route('dashboard');
    }
}
