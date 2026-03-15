<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\ConsultancyScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCompany extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'consultancy_id',
        'name',
        'tax_id',
        'active',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new ConsultancyScope);
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function consultancy(): BelongsTo
    {
        return $this->belongsTo(Consultancy::class);
    }
}
