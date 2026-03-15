<?php

declare(strict_types=1);

namespace App\Scopes;

use App\Enums\UserRole;
use App\Models\Consultancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ConsultancyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if ($user->role === UserRole::SuperAdmin) {
            return;
        }

        if ($model instanceof Consultancy) {
            $builder->where($model->getTable().'.id', $user->consultancy_id);

            return;
        }

        $builder->where($model->getTable().'.consultancy_id', $user->consultancy_id);
    }
}
