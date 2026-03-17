<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\ClientCompanies;

use App\Actions\ClientCompanies\CreateClientCompanyAction;
use App\Actions\ClientCompanies\ImportClientCompaniesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientCompanies\StoreClientCompanyRequest;
use App\Models\ClientCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientCompanyController extends Controller
{
    public function index(): Response
    {
        $clientCompanies = ClientCompany::paginate(25);

        return Inertia::render('ClientCompanies/Index', [
            'clientCompanies' => $clientCompanies,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ClientCompany::class);

        return Inertia::render('ClientCompanies/Create');
    }

    public function store(StoreClientCompanyRequest $request, CreateClientCompanyAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('client-companies.index');
    }

    public function import(Request $request, ImportClientCompaniesAction $action): RedirectResponse
    {
        $this->authorize('create', ClientCompany::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $result = $action->handle($request->file('file'));

        return redirect()->route('client-companies.index')
            ->with('import_result', $result);
    }
}
