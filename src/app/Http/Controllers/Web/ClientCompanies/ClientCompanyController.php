<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\ClientCompanies;

use App\Actions\ClientCompanies\CreateClientCompanyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientCompanies\StoreClientCompanyRequest;
use App\Models\ClientCompany;
use Illuminate\Http\RedirectResponse;
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
}
