<?php

declare(strict_types=1);

namespace App\Actions\ClientCompanies;

use App\Models\ClientCompany;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ImportClientCompaniesAction
{
    public function handle(UploadedFile $file): array
    {
        $sheets = Excel::toArray([], $file);
        $rows = $sheets[0] ?? [];

        $created = [];
        $skipped = [];
        $consultancyId = auth()->user()->consultancy_id;

        foreach ($rows as $row) {
            $name = trim((string) ($row[0] ?? ''));
            $taxId = strtoupper(trim((string) ($row[1] ?? '')));

            if ($name === '' || $taxId === '') {
                continue;
            }

            if (ClientCompany::withoutGlobalScopes()->where('tax_id', $taxId)->exists()) {
                $skipped[] = ['name' => $name, 'tax_id' => $taxId];
                continue;
            }

            ClientCompany::create([
                'consultancy_id' => $consultancyId,
                'name' => $name,
                'tax_id' => $taxId,
                'active' => true,
            ]);

            $created[] = ['name' => $name, 'tax_id' => $taxId];
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
