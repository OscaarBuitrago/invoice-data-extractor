<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceType;
use App\Enums\OcrStatus;
use App\Enums\OperationType;
use App\Enums\ValidationStatus;
use App\Scopes\ConsultancyScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    public const float OCR_CONFIDENCE_THRESHOLD = 0.70;

    protected $fillable = [
        'consultancy_id',
        'client_company_id',
        'upload_batch_id',
        'user_id',
        'file_path',
        'file_name',
        'type',
        'operation_type',
        'ocr_status',
        'validation_status',
        'ocr_confidence',
        'invoice_date',
        'invoice_number',
        'issuer_tax_id',
        'issuer_name',
        'taxable_base',
        'vat_percentage',
        'vat_amount',
        'irpf_percentage',
        'irpf_amount',
        'total',
        'ocr_raw',
        'validation_notes',
        'exported_to_sage',
        'exported_to_sage_at',
        'validated_at',
    ];

    protected $casts = [
        'type' => InvoiceType::class,
        'operation_type' => OperationType::class,
        'ocr_status' => OcrStatus::class,
        'validation_status' => ValidationStatus::class,
        'ocr_confidence' => 'float',
        'invoice_date' => 'date',
        'taxable_base' => 'float',
        'vat_percentage' => 'float',
        'vat_amount' => 'float',
        'irpf_percentage' => 'float',
        'irpf_amount' => 'float',
        'total' => 'float',
        'ocr_raw' => 'array',
        'exported_to_sage' => 'boolean',
        'exported_to_sage_at' => 'datetime',
        'validated_at' => 'datetime',
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

    public function uploadBatch(): BelongsTo
    {
        return $this->belongsTo(UploadBatch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function needsReview(): bool
    {
        return $this->ocr_confidence !== null && $this->ocr_confidence < self::OCR_CONFIDENCE_THRESHOLD;
    }
}
