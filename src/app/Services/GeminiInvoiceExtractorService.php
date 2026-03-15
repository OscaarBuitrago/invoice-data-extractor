<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\OcrResultData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GeminiInvoiceExtractorService
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
Eres un experto en análisis de facturas españolas. Tu tarea es extraer datos estructurados del documento adjunto.

═══ EMISOR Y RECEPTOR ═══

DEFINICIÓN CONCEPTUAL (aplica siempre):
- EMISOR = quien EMITE la factura, quien COBRA, quien VENDE o presta el servicio. Es el autor del documento.
- RECEPTOR = quien RECIBE la factura, quien PAGA, quien COMPRA o contrata el servicio.

SEÑALES VISUALES para identificar el EMISOR (busca en orden):
1. El bloque con el logo o membrete principal de la empresa, normalmente en la cabecera superior izquierda o centrado arriba.
2. Sección etiquetada como: "Emisor", "Datos del emisor", "Proveedor", "Vendedor", "Razón social", "De:", "Empresa".
3. El nombre/razón social que aparece PRIMERO en el documento, antes del número de factura.
4. El CIF/NIF que está en la cabecera o junto al nombre en la parte superior.

SEÑALES VISUALES para identificar el RECEPTOR (busca en orden):
1. Sección etiquetada como: "Receptor", "Destinatario", "Cliente", "Datos del cliente", "Facturar a:", "A:", "Para:".
2. Bloque con dirección de envío o fiscal del cliente, habitualmente debajo o a la derecha de los datos del emisor.
3. El segundo CIF/NIF que aparece en el documento.

REGLAS DE DESEMPATE (cuando hay ambigüedad):
- Si solo hay un nombre/CIF visible, es el EMISOR. El receptor puede estar ausente (devuelve null).
- El emisor es SIEMPRE quien pone el número de factura y la fecha — estos datos pertenecen al emisor.
- En facturas de autónomos/profesionales, el nombre al pie junto a "Firmado:" o en la cabecera es el emisor.
- Nunca intercambies emisor y receptor: si ves "Empresa A factura a Empresa B", A=emisor, B=receptor.

FORMATO de los identificadores fiscales:
- CIF empresarial: letra + 8 dígitos (ej. B12345678, A87654321).
- NIF de persona física: 8 dígitos + letra (ej. 12345678Z).
- NIE de extranjero: X/Y/Z + 7 dígitos + letra (ej. X1234567L).
- Pueden ir precedidos de la etiqueta "CIF:", "NIF:", "N.I.F.:", "C.I.F.:" — extrae solo el código, sin la etiqueta.

═══ FECHAS ═══
- Devuelve SIEMPRE en formato ISO 8601: YYYY-MM-DD.
- Facturas españolas usan DD/MM/YYYY o DD-MM-YYYY — convierte correctamente.
- Ejemplo: "10/2/2026" → "2026-02-10" (día 10, mes 2), NUNCA "2026-10-02".

═══ IMPORTES ═══
- Números decimales (float), sin símbolo de moneda ni separadores de miles.
- Separador decimal español es la coma — conviértela a punto: "1.210,50" → 1210.50.
- "Base imponible" o "Base" → taxable_base.
- "IVA" o "Impuesto sobre el Valor Añadido" → vat_amount y vat_percentage.
- "IRPF" o "Retención" → irpf_amount e irpf_percentage. Si no aparece → null.
- "Total" o "Total factura" → total.

═══ CONFIDENCE ═══
- 0.95–1.0: todos los campos visibles y sin ambigüedad.
- 0.70–0.94: algún campo ambiguo o parcialmente visible.
- < 0.70: factura ilegible o faltan datos clave.

Devuelve ÚNICAMENTE el JSON, sin texto adicional, sin bloques de código markdown.
PROMPT;

    private readonly string $apiKey;

    private readonly string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key');
        $this->model = (string) config('services.gemini.model', 'gemini-1.5-flash');
    }

    public function analyze(string $filePath): OcrResultData
    {
        $fileContent = Storage::disk('local')->get($filePath);

        if ($fileContent === null) {
            throw new RuntimeException("Cannot read file: {$filePath}");
        }

        $base64 = base64_encode($fileContent);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::post($url, [
            'system_instruction' => [
                'parts' => [['text' => self::SYSTEM_PROMPT]],
            ],
            'contents' => [[
                'parts' => [
                    [
                        'inline_data' => [
                            'mime_type' => 'application/pdf',
                            'data' => $base64,
                        ],
                    ],
                    ['text' => 'Extrae los datos de esta factura según las instrucciones.'],
                ],
            ]],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini API request failed: '.$response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text)) {
            throw new RuntimeException('Gemini API returned unexpected response structure');
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new RuntimeException('Gemini API returned invalid JSON: '.$text);
        }

        return $this->mapToOcrResult($data);
    }

    private function mapToOcrResult(array $data): OcrResultData
    {
        return new OcrResultData(
            invoiceDate: $this->nullableString($data['invoice_date'] ?? null),
            invoiceNumber: $this->nullableString($data['invoice_number'] ?? null),
            issuerTaxId: $this->nullableString($data['issuer_tax_id'] ?? null),
            issuerName: $this->nullableString($data['issuer_name'] ?? null),
            recipientTaxId: $this->nullableString($data['recipient_tax_id'] ?? null),
            recipientName: $this->nullableString($data['recipient_name'] ?? null),
            taxableBase: $this->nullableFloat($data['taxable_base'] ?? null),
            vatPercentage: $this->nullableFloat($data['vat_percentage'] ?? null),
            vatAmount: $this->nullableFloat($data['vat_amount'] ?? null),
            irpfPercentage: $this->nullableFloat($data['irpf_percentage'] ?? null),
            irpfAmount: $this->nullableFloat($data['irpf_amount'] ?? null),
            total: $this->nullableFloat($data['total'] ?? null),
            confidence: round((float) ($data['confidence'] ?? 0.0), 4),
            raw: $data,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
