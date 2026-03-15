<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\ConsultancyScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SageExport extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'consultancy_id',
        'client_company_id',
        'user_id',
        'total_invoices',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new ConsultancyScope);
    }

    public function consultancy(): BelongsTo
    {
        return $this->belongsTo(Consultancy::class);
    }

    public function clientCompany(): BelongsTo
    {
        return $this->belongsTo(ClientCompany::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'sage_export_invoice');
    }
}
