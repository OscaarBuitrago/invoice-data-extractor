import ConfidenceBadge from '@/Components/Invoices/ConfidenceBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type PageProps } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { Document, Page, pdfjs } from 'react-pdf';
import 'react-pdf/dist/Page/AnnotationLayer.css';
import 'react-pdf/dist/Page/TextLayer.css';

pdfjs.GlobalWorkerOptions.workerSrc = new URL(
    'pdfjs-dist/build/pdf.worker.min.mjs',
    import.meta.url,
).toString();

interface BatchInvoice {
    id: string;
    file_name: string;
    validation_status: string;
    ocr_confidence: number | null;
    ocr_status: string;
}

interface InvoiceData {
    id: string;
    file_name: string;
    ocr_confidence: number | null;
    ocr_status: string;
    validation_status: string;
    invoice_date: string | null;
    invoice_number: string | null;
    issuer_tax_id: string | null;
    issuer_name: string | null;
    recipient_tax_id: string | null;
    recipient_name: string | null;
    taxable_base: number | null;
    vat_percentage: number | null;
    vat_amount: number | null;
    irpf_percentage: number | null;
    irpf_amount: number | null;
    total: number | null;
    type: string;
    operation_type: string;
    validation_notes: string | null;
}

interface Props extends PageProps {
    invoice: InvoiceData;
    pdfUrl: string;
    batch: { id: string; total_invoices: number };
    batchInvoices: BatchInvoice[];
}

const STATUS_STYLES: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-600',
    validated: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
};

const STATUS_LABELS: Record<string, string> = {
    pending: 'Pendiente',
    validated: 'Validada',
    rejected: 'Rechazada',
};

const OPERATION_TYPES = [
    { value: 'normal', label: 'Normal' },
    { value: 'intra_community', label: 'Intracomunitaria' },
    { value: 'reverse_charge', label: 'Inversión sujeto pasivo' },
    { value: 'import', label: 'Importación' },
    { value: 'not_subject', label: 'No sujeta' },
];

export default function Validation({ invoice, pdfUrl, batch, batchInvoices }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        action: 'validate' as 'validate' | 'reject',
        invoice_date: invoice.invoice_date ?? '',
        invoice_number: invoice.invoice_number ?? '',
        issuer_tax_id: invoice.issuer_tax_id ?? '',
        issuer_name: invoice.issuer_name ?? '',
        recipient_tax_id: invoice.recipient_tax_id ?? '',
        recipient_name: invoice.recipient_name ?? '',
        taxable_base: invoice.taxable_base ?? '',
        vat_percentage: invoice.vat_percentage ?? '',
        vat_amount: invoice.vat_amount ?? '',
        irpf_percentage: invoice.irpf_percentage ?? '',
        irpf_amount: invoice.irpf_amount ?? '',
        total: invoice.total ?? '',
        type: invoice.type ?? 'received',
        operation_type: invoice.operation_type ?? 'normal',
        validation_notes: invoice.validation_notes ?? '',
    });

    useEffect(() => {
        setData({
            action: 'validate',
            invoice_date: invoice.invoice_date ?? '',
            invoice_number: invoice.invoice_number ?? '',
            issuer_tax_id: invoice.issuer_tax_id ?? '',
            issuer_name: invoice.issuer_name ?? '',
            recipient_tax_id: invoice.recipient_tax_id ?? '',
            recipient_name: invoice.recipient_name ?? '',
            taxable_base: invoice.taxable_base ?? '',
            vat_percentage: invoice.vat_percentage ?? '',
            vat_amount: invoice.vat_amount ?? '',
            irpf_percentage: invoice.irpf_percentage ?? '',
            irpf_amount: invoice.irpf_amount ?? '',
            total: invoice.total ?? '',
            type: invoice.type ?? 'received',
            operation_type: invoice.operation_type ?? 'normal',
            validation_notes: invoice.validation_notes ?? '',
        });
    }, [invoice.id]);

    const isReceived = data.type === 'received';

    const submit = (action: 'validate' | 'reject') => {
        setData('action', action);
        put(route('invoices.update', invoice.id), { preserveScroll: true });
    };

    // Keyboard: Enter = validate, Escape = reject
    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if (e.target instanceof HTMLTextAreaElement) return;
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                submit('validate');
            }
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [data]);

    const needsReview = invoice.ocr_confidence !== null && invoice.ocr_confidence < 0.7;

    return (
        <AuthenticatedLayout>
            <Head title={`Validar — ${invoice.file_name}`} />

            <div className="flex h-[calc(100vh-4rem)] overflow-hidden">

                {/* ── Sidebar: lista de facturas del lote ── */}
                <aside className="hidden w-56 shrink-0 overflow-y-auto border-r border-gray-200 bg-white xl:block">
                    <div className="border-b border-gray-100 px-3 py-3">
                        <p className="text-xs font-medium uppercase tracking-wider text-gray-400">
                            Lote · {batchInvoices.length} facturas
                        </p>
                    </div>
                    <ul>
                        {batchInvoices.map((inv) => (
                            <li key={inv.id}>
                                <Link
                                    href={route('invoices.show', inv.id)}
                                    className={`flex items-center gap-2 px-3 py-2.5 text-xs hover:bg-gray-50 ${
                                        inv.id === invoice.id ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-700'
                                    }`}
                                >
                                    <span
                                        className={`inline-block h-2 w-2 shrink-0 rounded-full ${
                                            STATUS_STYLES[inv.validation_status]?.includes('green')
                                                ? 'bg-green-400'
                                                : inv.validation_status === 'rejected'
                                                  ? 'bg-red-400'
                                                  : 'bg-gray-300'
                                        }`}
                                    />
                                    <span className="truncate">{inv.file_name}</span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </aside>

                {/* ── PDF viewer ── */}
                <div className="flex flex-1 flex-col overflow-hidden bg-gray-700">
                    <div className="flex items-center justify-between border-b border-gray-600 px-4 py-2">
                        <span className="text-sm font-medium text-gray-200">{invoice.file_name}</span>
                        <div className="flex items-center gap-3">
                            <ConfidenceBadge confidence={invoice.ocr_confidence} />
                            {needsReview && (
                                <span className="text-xs text-red-300">⚠ Revisar con atención</span>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-1 items-start justify-center overflow-y-auto p-4">
                        <Document file={pdfUrl} loading={<p className="text-sm text-gray-400">Cargando PDF...</p>}>
                            <Page pageNumber={1} width={600} />
                        </Document>
                    </div>
                </div>

                {/* ── Validation form ── */}
                <div className="flex w-96 shrink-0 flex-col overflow-y-auto border-l border-gray-200 bg-white">
                    <div className="border-b border-gray-100 px-4 py-3">
                        <h2 className="text-sm font-semibold text-gray-800">Datos de la factura</h2>
                        <p className="mt-0.5 text-xs text-gray-400">
                            <span className={`rounded px-1.5 py-0.5 text-xs font-medium ${STATUS_STYLES[invoice.validation_status]}`}>
                                {STATUS_LABELS[invoice.validation_status]}
                            </span>
                        </p>
                    </div>

                    <form className="flex-1 space-y-4 p-4" onSubmit={(e) => e.preventDefault()}>

                        {/* Tipo */}
                        <div className="grid grid-cols-2 gap-3">
                            <Field label="Tipo">
                                <select
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value as 'received' | 'issued')}
                                    className={selectCls}
                                >
                                    <option value="received">Recibida</option>
                                    <option value="issued">Emitida</option>
                                </select>
                            </Field>
                            <Field label="Tipo operación">
                                <select
                                    value={data.operation_type}
                                    onChange={(e) => setData('operation_type', e.target.value)}
                                    className={selectCls}
                                >
                                    {OPERATION_TYPES.map((o) => (
                                        <option key={o.value} value={o.value}>{o.label}</option>
                                    ))}
                                </select>
                            </Field>
                        </div>

                        <Field label="Fecha factura" error={errors.invoice_date}>
                            <input type="date" value={data.invoice_date} onChange={(e) => setData('invoice_date', e.target.value)} className={inputCls(!!errors.invoice_date)} />
                        </Field>

                        <Field label="Nº Factura" error={errors.invoice_number}>
                            <input type="text" value={data.invoice_number} onChange={(e) => setData('invoice_number', e.target.value)} className={inputCls(!!errors.invoice_number)} />
                        </Field>

                        {isReceived ? (
                            <>
                                <Field label="CIF Emisor" error={errors.issuer_tax_id}>
                                    <input type="text" value={data.issuer_tax_id} onChange={(e) => setData('issuer_tax_id', e.target.value)} className={inputCls(!!errors.issuer_tax_id)} />
                                </Field>
                                <Field label="Nombre Emisor">
                                    <input type="text" value={data.issuer_name} onChange={(e) => setData('issuer_name', e.target.value)} className={inputCls(false)} />
                                </Field>
                            </>
                        ) : (
                            <>
                                <Field label="CIF Receptor" error={errors.recipient_tax_id}>
                                    <input type="text" value={data.recipient_tax_id} onChange={(e) => setData('recipient_tax_id', e.target.value)} className={inputCls(!!errors.recipient_tax_id)} />
                                </Field>
                                <Field label="Nombre Receptor">
                                    <input type="text" value={data.recipient_name} onChange={(e) => setData('recipient_name', e.target.value)} className={inputCls(false)} />
                                </Field>
                            </>
                        )}

                        <div className="grid grid-cols-2 gap-3">
                            <Field label="Base imponible" error={errors.taxable_base}>
                                <input type="number" step="0.01" value={data.taxable_base} onChange={(e) => setData('taxable_base', e.target.value)} className={inputCls(!!errors.taxable_base)} />
                            </Field>
                            <Field label="% IVA">
                                <input type="number" step="0.01" value={data.vat_percentage} onChange={(e) => setData('vat_percentage', e.target.value)} className={inputCls(false)} />
                            </Field>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <Field label="Cuota IVA">
                                <input type="number" step="0.01" value={data.vat_amount} onChange={(e) => setData('vat_amount', e.target.value)} className={inputCls(false)} />
                            </Field>
                            <Field label="Total" error={errors.total}>
                                <input type="number" step="0.01" value={data.total} onChange={(e) => setData('total', e.target.value)} className={inputCls(!!errors.total)} />
                            </Field>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <Field label="% IRPF">
                                <input type="number" step="0.01" value={data.irpf_percentage} onChange={(e) => setData('irpf_percentage', e.target.value)} className={inputCls(false)} placeholder="0" />
                            </Field>
                            <Field label="Cuota IRPF">
                                <input type="number" step="0.01" value={data.irpf_amount} onChange={(e) => setData('irpf_amount', e.target.value)} className={inputCls(false)} placeholder="0" />
                            </Field>
                        </div>

                        <Field label="Notas">
                            <textarea
                                value={data.validation_notes}
                                onChange={(e) => setData('validation_notes', e.target.value)}
                                rows={2}
                                className={inputCls(false)}
                                placeholder="Observaciones opcionales..."
                            />
                        </Field>
                    </form>

                    {/* Actions */}
                    <div className="border-t border-gray-100 p-4">
                        <button
                            onClick={() => submit('validate')}
                            disabled={processing}
                            className="w-full rounded-md bg-indigo-600 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Confirmar y siguiente <kbd className="ml-1 rounded bg-indigo-500 px-1 text-xs">↵</kbd>
                        </button>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div>
            <label className="mb-1 block text-xs font-medium text-gray-600">{label}</label>
            {children}
            {error && <p className="mt-1 text-xs text-red-500">{error}</p>}
        </div>
    );
}

const inputCls = (hasError: boolean) =>
    `block w-full rounded-md border px-2.5 py-1.5 text-sm shadow-sm focus:outline-none focus:ring-1 ${
        hasError
            ? 'border-red-300 focus:border-red-400 focus:ring-red-400'
            : 'border-gray-300 focus:border-indigo-400 focus:ring-indigo-400'
    }`;

const selectCls =
    'block w-full rounded-md border border-gray-300 px-2.5 py-1.5 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400';
