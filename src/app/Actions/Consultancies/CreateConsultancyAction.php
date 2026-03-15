<?php

declare(strict_types=1);

namespace App\Actions\Consultancies;

use App\Models\Consultancy;
use Illuminate\Support\Facades\DB;

class CreateConsultancyAction
{
    public function handle(array $data): Consultancy
    {
        return DB::transaction(fn (): Consultancy => Consultancy::create([
            'name' => $data['name'],
            'tax_id' => $data['tax_id'],
            'active' => true,
        ]));
    }
}
