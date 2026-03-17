<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Consultancies;

use App\Actions\Consultancies\CreateConsultancyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Consultancies\StoreConsultancyRequest;
use App\Models\Consultancy;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConsultancyController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Consultancy::class);

        $consultancies = Consultancy::withoutGlobalScopes()
            ->with(['users' => fn ($q) => $q->whereIn('role', ['admin', 'consultant'])
                ->orderBy('role')
                ->orderBy('name')])
            ->paginate(25);

        return Inertia::render('Consultancies/Index', [
            'consultancies' => $consultancies,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Consultancy::class);

        return Inertia::render('Consultancies/Create');
    }

    public function store(StoreConsultancyRequest $request, CreateConsultancyAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('consultancies.index');
    }
}
