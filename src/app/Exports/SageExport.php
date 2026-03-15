<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\OperationType;
use App\Models\Invoice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SageExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $invoices,
    ) {}

    public function collection(): Collection
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            'FacturaRegistro',
            'Serie',
            'Su Factura',
            'Fecha Expedición',
            'Fecha Operación',
            'Fecha Registro',
            'CodigoCuenta',
            'CIFEUROPEO',
            'Proveedor',
            'Comentario SII',
            'Contrapartida',
            'CodigoTransaccion',
            'ClaveOperacionFactura',
            'Importe Factura',
            'Base Imponible1',
            '%Iva1',
            'Cuota Iva1',
            '%RecEq1',
            'Cuota Rec1',
            'CodigoRetencion',
            'Base Retención',
            '%Retención',
            'Cuota Retenc.',
            'Base Imponible2',
            '%Iva2',
            'Cuota Iva2',
            '%RecEq2',
            'Cuota Rec2',
            'TipoRectificativa',
            'ClaseAbonoRectificativas',
            'EjercicioFacturaRectificada',
            'SerieFacturaRectificada',
            'NumeroFacturaRectificada',
            'FechaFacturaRectificada',
            'BaseImponibleRectificada',
            'CuotaIvaRectificada',
            'RecargoEquiRectificada',
            'NumeroFActuraInicial',
            'NumeroFacturaFinal',
            'IdFacturaExterno',
            'Codigo Postal',
            'Cod. Provincia',
            'Provincia',
            'CodigoCanal',
            'CodigoDelegación',
            'CodDepartamento',
        ];
    }

    public function map(mixed $invoice): array
    {
        /** @var Invoice $invoice */
        $invoiceDate = $invoice->invoice_date?->format('d/m/Y') ?? '';
        $registrationDate = $invoice->validated_at?->format('d/m/Y') ?? now()->format('d/m/Y');
        $hasIrpf = $invoice->irpf_amount !== null && $invoice->irpf_amount > 0;

        return [
            $invoice->invoice_number,               // FacturaRegistro
            '',                                      // Serie
            $invoice->invoice_number,               // Su Factura
            $invoiceDate,                            // Fecha Expedición
            $invoiceDate,                            // Fecha Operación
            $registrationDate,                       // Fecha Registro
            '',                                      // CodigoCuenta
            $invoice->issuer_tax_id,                // CIFEUROPEO
            $invoice->issuer_name,                  // Proveedor
            '',                                      // Comentario SII
            '',                                      // Contrapartida
            $this->mapTransactionCode($invoice->operation_type), // CodigoTransaccion
            $this->mapSiiKey($invoice->operation_type),          // ClaveOperacionFactura
            $invoice->total,                        // Importe Factura
            $invoice->taxable_base,                 // Base Imponible1
            $invoice->vat_percentage,               // %Iva1
            $invoice->vat_amount,                   // Cuota Iva1
            '',                                      // %RecEq1
            '',                                      // Cuota Rec1
            $hasIrpf ? '01' : '',                   // CodigoRetencion
            $hasIrpf ? $invoice->taxable_base : '', // Base Retención
            $hasIrpf ? $invoice->irpf_percentage : '', // %Retención
            $hasIrpf ? $invoice->irpf_amount : '',  // Cuota Retenc.
            '', '', '', '', '',                      // Base/Cuota IVA2 + RecEq2
            '', '', '', '', '', '', '', '', '',      // Rectificativas
            '', '', '',                              // NumeroFactura Inicial/Final/IdExterno
            '', '', '',                              // CP/Provincia
            '', '', '',                              // Canal/Delegación/Departamento
        ];
    }

    private function mapTransactionCode(OperationType $type): string
    {
        return match ($type) {
            OperationType::IntraCommunity => '09',
            OperationType::ReverseCharge => '12',
            OperationType::Import => '13',
            default => '01',
        };
    }

    private function mapSiiKey(OperationType $type): string
    {
        return match ($type) {
            OperationType::IntraCommunity => 'F5',
            OperationType::ReverseCharge => 'F4',
            OperationType::Import => 'F5',
            default => 'F1',
        };
    }
}
