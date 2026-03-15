<?php

declare(strict_types=1);

use App\Data\OcrResultData;
use App\Services\GeminiInvoiceExtractorService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    config(['services.gemini.api_key' => 'test-api-key']);
    config(['services.gemini.model' => 'gemini-1.5-flash']);
});

function geminiResponse(array $fields): array
{
    return [
        'candidates' => [[
            'content' => [
                'parts' => [[
                    'text' => json_encode($fields),
                ]],
            ],
        ]],
    ];
}

it('parses a successful gemini response into OcrResultData', function (): void {
    Storage::disk('local')->put('private/invoices/test/factura.pdf', 'fake-pdf-content');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(geminiResponse([
            'invoice_date' => '2026-02-10',
            'invoice_number' => 'FAC-2026-001',
            'issuer_tax_id' => 'B12345678',
            'issuer_name' => 'Proveedor S.L.',
            'recipient_tax_id' => 'A87654321',
            'recipient_name' => 'Cliente S.A.',
            'taxable_base' => 1000.00,
            'vat_percentage' => 21.0,
            'vat_amount' => 210.00,
            'irpf_percentage' => null,
            'irpf_amount' => null,
            'total' => 1210.00,
            'confidence' => 0.97,
        ]), 200),
    ]);

    $result = app(GeminiInvoiceExtractorService::class)->analyze('invoices/test/factura.pdf');

    expect($result)->toBeInstanceOf(OcrResultData::class)
        ->and($result->invoiceDate)->toBe('2026-02-10')
        ->and($result->invoiceNumber)->toBe('FAC-2026-001')
        ->and($result->issuerTaxId)->toBe('B12345678')
        ->and($result->issuerName)->toBe('Proveedor S.L.')
        ->and($result->recipientTaxId)->toBe('A87654321')
        ->and($result->total)->toBe(1210.00)
        ->and($result->confidence)->toBe(0.97);
});

it('calls the correct gemini endpoint with the api key', function (): void {
    Storage::disk('local')->put('private/invoices/test/factura.pdf', 'fake-pdf-content');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(geminiResponse([
            'invoice_date' => null, 'invoice_number' => null,
            'issuer_tax_id' => null, 'issuer_name' => null,
            'recipient_tax_id' => null, 'recipient_name' => null,
            'taxable_base' => null, 'vat_percentage' => null, 'vat_amount' => null,
            'irpf_percentage' => null, 'irpf_amount' => null, 'total' => null,
            'confidence' => 0.5,
        ]), 200),
    ]);

    app(GeminiInvoiceExtractorService::class)->analyze('invoices/test/factura.pdf');

    Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'gemini-1.5-flash')
        && str_contains((string) $request->url(), 'test-api-key'));
});

it('throws RuntimeException when gemini api returns an error', function (): void {
    Storage::disk('local')->put('private/invoices/test/factura.pdf', 'fake-pdf-content');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(['error' => 'Invalid API key'], 400),
    ]);

    expect(fn () => app(GeminiInvoiceExtractorService::class)->analyze('invoices/test/factura.pdf'))
        ->toThrow(RuntimeException::class);
});

it('throws RuntimeException when gemini returns invalid json', function (): void {
    Storage::disk('local')->put('private/invoices/test/factura.pdf', 'fake-pdf-content');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [['text' => 'not valid json {']]],
            ]],
        ], 200),
    ]);

    expect(fn () => app(GeminiInvoiceExtractorService::class)->analyze('invoices/test/factura.pdf'))
        ->toThrow(RuntimeException::class);
});

it('handles null fields gracefully', function (): void {
    Storage::disk('local')->put('private/invoices/test/factura.pdf', 'fake-pdf-content');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(geminiResponse([
            'invoice_date' => null, 'invoice_number' => null,
            'issuer_tax_id' => null, 'issuer_name' => null,
            'recipient_tax_id' => null, 'recipient_name' => null,
            'taxable_base' => null, 'vat_percentage' => null, 'vat_amount' => null,
            'irpf_percentage' => null, 'irpf_amount' => null, 'total' => null,
            'confidence' => 0.3,
        ]), 200),
    ]);

    $result = app(GeminiInvoiceExtractorService::class)->analyze('invoices/test/factura.pdf');

    expect($result->invoiceDate)->toBeNull()
        ->and($result->issuerTaxId)->toBeNull()
        ->and($result->confidence)->toBe(0.3);
});
