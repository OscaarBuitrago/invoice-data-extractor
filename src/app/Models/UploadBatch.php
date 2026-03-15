<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UploadBatchStatus;
use App\Scopes\ConsultancyScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadBatch extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'consultancy_id',
        'client_company_id',
        'user_id',
        'status',
        'total_invoices',
        'processed_invoices',
    ];

    protected $casts = [
        'status' => UploadBatchStatus::class,
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

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
