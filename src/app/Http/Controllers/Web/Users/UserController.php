<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Users;

use App\Actions\Users\CreateUserAction;
use App\Actions\Users\UpdateUserAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Consultancy;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('consultancy')
            ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->where('consultancy_id', auth()->user()->consultancy_id))
            ->paginate(25);

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        $consultancies = auth()->user()->isSuperAdmin()
            ? Consultancy::withoutGlobalScopes()->get(['id', 'name'])
            : collect();

        $roles = collect(UserRole::cases())
            ->filter(fn ($role) => $role !== UserRole::SuperAdmin)
            ->map(fn ($role) => [
                'value' => $role->value,
                'label' => match ($role) {
                    UserRole::Admin => 'Admin',
                    UserRole::Consultant => 'Consultor',
                    default => $role->value,
                },
            ])
            ->values();

        return Inertia::render('Users/Create', [
            'consultancies' => $consultancies,
            'roles' => $roles,
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('users.index');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $roles = collect(UserRole::cases())
            ->filter(fn ($role) => $role !== UserRole::SuperAdmin)
            ->map(fn ($role) => [
                'value' => $role->value,
                'label' => match ($role) {
                    UserRole::Admin => 'Admin',
                    UserRole::Consultant => 'Consultor',
                    default => $role->value,
                },
            ])
            ->values();

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $action->handle($user, $request->validated());

        return redirect()->route('users.index');
    }
}
