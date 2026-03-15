<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\OcrResultData;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AzureFormRecognizerService
{
    private readonly string $endpoint;

    private readonly string $key;

    public function __construct()
    {
        $this->endpoint = rtrim((string) config('services.azure.form_recognizer_endpoint'), '/');
        $this->key = (string) config('services.azure.form_recognizer_key');
    }

    public function analyze(string $filePath): OcrResultData
    {
        $fileContent = file_get_contents(storage_path('app/private/'.$filePath));

        if ($fileContent === false) {
            throw new RuntimeException("Cannot read file: {$filePath}");
        }

        $analyzeUrl = "{$this->endpoint}/formrecognizer/documentModels/prebuilt-invoice:analyze?api-version=2023-07-31";

        $submitResponse = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->key,
            'Content-Type' => 'application/pdf',
        ])->withBody($fileContent, 'application/pdf')->post($analyzeUrl);

        if (! $submitResponse->successful()) {
            throw new RuntimeException('Azure Form Recognizer submit failed: '.$submitResponse->body());
        }

        $operationUrl = $submitResponse->header('Operation-Location');

        if (! $operationUrl) {
            throw new RuntimeException('Azure Form Recognizer did not return Operation-Location header');
        }

        return $this->pollResult($operationUrl);
    }

    private function pollResult(string $operationUrl): OcrResultData
    {
        $maxAttempts = 30;

        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(2);

            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->key,
            ])->get($operationUrl);

            if (! $response->successful()) {
                throw new RuntimeException('Azure polling failed: '.$response->body());
            }

            $data = $response->json();

            if (($data['status'] ?? '') === 'succeeded') {
                return $this->parseResult($data);
            }

            if (($data['status'] ?? '') === 'failed') {
                throw new RuntimeException('Azure Form Recognizer analysis failed');
            }
        }

        throw new RuntimeException('Azure Form Recognizer timed out after polling');
    }

    private function parseResult(array $data): OcrResultData
    {
        $fields = $data['analyzeResult']['documents'][0]['fields'] ?? [];

        $invoiceDate = $this->fieldValue($fields, 'InvoiceDate');
        $invoiceNumber = $this->fieldValue($fields, 'InvoiceId');
        $issuerTaxId = $this->fieldValue($fields, 'VendorTaxId');
        $issuerName = $this->fieldValue($fields, 'VendorName');
        $taxableBase = $this->fieldFloatValue($fields, 'SubTotal');
        $vatAmount = $this->fieldFloatValue($fields, 'TotalTax');
        $total = $this->fieldFloatValue($fields, 'InvoiceTotal');

        $vatPercentage = $taxableBase && $vatAmount && $taxableBase > 0
            ? round($vatAmount / $taxableBase * 100, 2)
            : null;

        $irpfAmount = $this->fieldFloatValue($fields, 'TotalDiscount');
        $irpfPercentage = $taxableBase && $irpfAmount && $taxableBase > 0
            ? round($irpfAmount / $taxableBase * 100, 2)
            : null;

        $keyConfidences = array_filter([
            $this->fieldConfidence($fields, 'InvoiceDate'),
            $this->fieldConfidence($fields, 'VendorTaxId'),
            $this->fieldConfidence($fields, 'SubTotal'),
            $this->fieldConfidence($fields, 'InvoiceTotal'),
        ], fn (?float $v): bool => $v !== null);

        $confidence = count($keyConfidences) > 0
            ? array_sum($keyConfidences) / count($keyConfidences)
            : 0.0;

        return new OcrResultData(
            invoiceDate: $invoiceDate,
            invoiceNumber: $invoiceNumber,
            issuerTaxId: $issuerTaxId,
            issuerName: $issuerName,
            taxableBase: $taxableBase,
            vatPercentage: $vatPercentage,
            vatAmount: $vatAmount,
            irpfPercentage: $irpfPercentage,
            irpfAmount: $irpfAmount,
            total: $total,
            confidence: round($confidence, 4),
            raw: $data['analyzeResult'] ?? [],
        );
    }

    private function fieldValue(array $fields, string $key): ?string
    {
        $value = $fields[$key]['valueString'] ?? $fields[$key]['content'] ?? null;

        return is_string($value) ? $value : null;
    }

    private function fieldFloatValue(array $fields, string $key): ?float
    {
        $value = $fields[$key]['valueCurrency']['amount'] ?? $fields[$key]['valueNumber'] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }

    private function fieldConfidence(array $fields, string $key): ?float
    {
        $value = $fields[$key]['confidence'] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }
}
