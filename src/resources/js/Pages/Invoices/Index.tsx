import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type PageProps, type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useRef, useState } from 'react';

interface Invoice {
    id: string;
    file_name: string;
    invoice_date: string | null;
    invoice_number: string | null;
    issuer_name: string | null;
    issuer_tax_id: string | null;
    recipient_name: string | null;
    recipient_tax_id: string | null;
    taxable_base: number | null;
    vat_amount: number | null;
    irpf_amount: number | null;
    total: number | null;
    type: string;
    ocr_status: string;
    validation_status: string;
    ocr_confidence: number | null;
    exported_to_sage: boolean;
}

interface Filters {
    type: string | null;
    validation_status: string | null;
    operation_type: string | null;
    date_from: string | null;
    date_to: string | null;
    exported_to_sage: string | null;
    invoice_number: string | null;
}

interface Props extends PageProps {
    invoices: PaginatedData<Invoice>;
    filters: Filters;
}

const VALIDATION_STYLES: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-600',
    validated: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
};

const VALIDATION_LABELS: Record<string, string> = {
    pending: 'Pendiente',
    validated: 'Validada',
    rejected: 'Rechazada',
};

const fmt = (n: number | null) =>
    n !== null ? n.toLocaleString('es-ES', { minimumFractionDigits: 2 }) + ' €' : '—';

const fmtDate = (d: string | null) =>
    d ? new Date(d).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '—';

export default function InvoicesIndex({ invoices, filters }: Props) {
    const [selected, setSelected] = useState<Set<string>>(new Set());
    const [localFilters, setLocalFilters] = useState(filters);
    const [exportWarning, setExportWarning] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Invoice | null>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const counterpartyLabel =
        filters.type === 'received' ? 'Emisor' : filters.type === 'issued' ? 'Receptor' : 'Contraparte';

    const toggleAll = () => {
        if (selected.size === invoices.data.length) {
            setSelected(new Set());
        } else {
            setSelected(new Set(invoices.data.map((i) => i.id)));
        }
    };

    const toggle = (id: string) => {
        const next = new Set(selected);
        next.has(id) ? next.delete(id) : next.add(id);
        setSelected(next);
    };

    const applyFilters = (updated: Filters) => {
        router.get(
            route('invoices.index'),
            Object.fromEntries(Object.entries(updated).filter(([, v]) => v !== null && v !== '')),
            { preserveState: true, replace: true },
        );
    };

    const setFilter = (key: keyof Filters, value: string | null) => {
        const updated = { ...localFilters, [key]: value };
        setLocalFilters(updated);
        applyFilters(updated);
    };

    const setFilterDebounced = (key: keyof Filters, value: string | null) => {
        const updated = { ...localFilters, [key]: value };
        setLocalFilters(updated);
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => applyFilters(updated), 400);
    };

    const clearFilters = () => {
        const empty: Filters = { type: null, validation_status: null, operation_type: null, date_from: null, date_to: null, exported_to_sage: null, invoice_number: null };
        setLocalFilters(empty);
        router.get(route('invoices.index'), {}, { replace: true });
    };

    const hasFilters = Object.values(localFilters).some((v) => v !== null && v !== '');

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Listado de Facturas</h2>
                    <Link
                        href={route('invoices.upload.create')}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Subir facturas
                    </Link>
                </div>
            }
        >
            <Head title="Facturas" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">

                    {/* Filters */}
                    <div className="rounded-lg bg-white p-4 shadow-sm">
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                            <input
                                type="text"
                                value={localFilters.invoice_number ?? ''}
                                onChange={(e) => setFilterDebounced('invoice_number', e.target.value || null)}
                                className={selectCls}
                                placeholder="Nº Factura"
                            />

                            <select
                                value={localFilters.type ?? ''}
                                onChange={(e) => setFilter('type', e.target.value || null)}
                                className={selectCls}
                            >
                                <option value="">Tipo</option>
                                <option value="received">Recibida</option>
                                <option value="issued">Emitida</option>
                            </select>

                            <select
                                value={localFilters.validation_status ?? ''}
                                onChange={(e) => setFilter('validation_status', e.target.value || null)}
                                className={selectCls}
                            >
                                <option value="">Estado</option>
                                <option value="pending">Pendiente</option>
                                <option value="validated">Validada</option>
                                <option value="rejected">Rechazada</option>
                            </select>

                            <select
                                value={localFilters.operation_type ?? ''}
                                onChange={(e) => setFilter('operation_type', e.target.value || null)}
                                className={selectCls}
                            >
                                <option value="">Operación</option>
                                <option value="normal">Normal</option>
                                <option value="intra_community">Intracomunitaria</option>
                                <option value="reverse_charge">Inversión s. pasivo</option>
                                <option value="import">Importación</option>
                                <option value="not_subject">No sujeta</option>
                            </select>

                            <input
                                type="date"
                                value={localFilters.date_from ?? ''}
                                onChange={(e) => setFilter('date_from', e.target.value || null)}
                                className={selectCls}
                            />

                            <input
                                type="date"
                                value={localFilters.date_to ?? ''}
                                onChange={(e) => setFilter('date_to', e.target.value || null)}
                                className={selectCls}
                            />
                        </div>

                        {hasFilters && (
                            <div className="mt-3">
                                <button onClick={clearFilters} className="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50">
                                    Limpiar filtros
                                </button>
                            </div>
                        )}
                    </div>

                    {/* Table */}
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="w-10 px-4 py-3">
                                        <input type="checkbox" checked={selected.size === invoices.data.length && invoices.data.length > 0} onChange={toggleAll} className="rounded border-gray-300 text-indigo-600" />
                                    </th>
                                    <th className={thCls}>Fecha</th>
                                    <th className={thCls}>Nº Factura</th>
                                    <th className={thCls}>{counterpartyLabel}</th>
                                    <th className={thCls}>Base imp.</th>
                                    <th className={thCls}>IVA</th>
                                    <th className={thCls}>IRPF</th>
                                    <th className={thCls}>Total</th>
                                    <th className={thCls}>Estado</th>
                                    <th className={thCls}></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {invoices.data.length === 0 && (
                                    <tr>
                                        <td colSpan={10} className="px-6 py-10 text-center text-sm text-gray-400">
                                            No hay facturas con los filtros seleccionados.
                                        </td>
                                    </tr>
                                )}
                                {invoices.data.map((invoice) => (
                                    <tr
                                        key={invoice.id}
                                        className={`hover:bg-gray-50 ${selected.has(invoice.id) ? 'bg-indigo-50' : ''}`}
                                    >
                                        <td className="px-4 py-3">
                                            {invoice.ocr_status !== 'duplicate' && (
                                                <input
                                                    type="checkbox"
                                                    checked={selected.has(invoice.id)}
                                                    onChange={() => toggle(invoice.id)}
                                                    className="rounded border-gray-300 text-indigo-600"
                                                />
                                            )}
                                        </td>
                                        <td className={tdCls}>{fmtDate(invoice.invoice_date)}</td>
                                        <td className={tdCls}>{invoice.invoice_number ?? '—'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-700">
                                            {invoice.type === 'received' ? (
                                                <>
                                                    <div className="font-medium">{invoice.issuer_name ?? '—'}</div>
                                                    <div className="text-xs text-gray-400">{invoice.issuer_tax_id}</div>
                                                </>
                                            ) : (
                                                <>
                                                    <div className="font-medium">{invoice.recipient_name ?? '—'}</div>
                                                    <div className="text-xs text-gray-400">{invoice.recipient_tax_id}</div>
                                                </>
                                            )}
                                        </td>
                                        <td className={`${tdCls} text-right`}>{fmt(invoice.taxable_base)}</td>
                                        <td className={`${tdCls} text-right`}>{fmt(invoice.vat_amount)}</td>
                                        <td className={`${tdCls} text-right`}>{invoice.irpf_amount ? fmt(invoice.irpf_amount) : '—'}</td>
                                        <td className={`${tdCls} text-right font-medium`}>{fmt(invoice.total)}</td>
                                        <td className="px-4 py-3">
                                            {invoice.ocr_status === 'duplicate' ? (
                                                <span className="inline-flex rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-700">
                                                    Duplicada
                                                </span>
                                            ) : invoice.exported_to_sage ? (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                                    <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                    </svg>
                                                    Exportada
                                                </span>
                                            ) : (
                                                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${VALIDATION_STYLES[invoice.validation_status]}`}>
                                                    {VALIDATION_LABELS[invoice.validation_status]}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-3">
                                                {invoice.ocr_status !== 'duplicate' && (
                                                    <Link
                                                        href={route('invoices.show', invoice.id)}
                                                        className="text-sm text-indigo-600 hover:text-indigo-800"
                                                    >
                                                        Revisar
                                                    </Link>
                                                )}
                                                <button
                                                    onClick={() => setDeleteTarget(invoice)}
                                                    className="text-gray-400 hover:text-red-500"
                                                    title="Eliminar factura"
                                                >
                                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={1.8} stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {invoices.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-gray-200 px-4 py-3">
                                <p className="text-sm text-gray-500">
                                    {invoices.total} facturas
                                </p>
                                <div className="flex gap-1">
                                    {invoices.links.map((link, i) => (
                                        <Link
                                            key={i}
                                            href={link.url ?? '#'}
                                            className={`rounded px-3 py-1 text-sm ${
                                                link.active
                                                    ? 'bg-indigo-600 text-white'
                                                    : link.url
                                                      ? 'text-gray-600 hover:bg-gray-100'
                                                      : 'cursor-default text-gray-300'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Floating action bar */}
            {selected.size > 0 && (
                <div className="fixed bottom-6 left-1/2 -translate-x-1/2">
                    <div className="flex items-center gap-4 rounded-xl bg-gray-900 px-6 py-3 shadow-xl">
                        <span className="text-sm font-medium text-white">
                            {selected.size} {selected.size === 1 ? 'factura seleccionada' : 'facturas seleccionadas'}
                        </span>
                        <div className="h-4 w-px bg-gray-600" />
                        <button
                            onClick={() => {
                                const nonValidated = invoices.data.filter(
                                    (i) => selected.has(i.id) && i.validation_status !== 'validated',
                                );
                                if (nonValidated.length > 0) {
                                    setExportWarning(nonValidated.length);
                                    return;
                                }
                                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = route('sage-exports.store');
                                const csrfInput = document.createElement('input');
                                csrfInput.type = 'hidden';
                                csrfInput.name = '_token';
                                csrfInput.value = token;
                                form.appendChild(csrfInput);
                                [...selected].forEach((id) => {
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = 'invoice_ids[]';
                                    input.value = id;
                                    form.appendChild(input);
                                });
                                document.body.appendChild(form);
                                form.submit();
                                document.body.removeChild(form);
                            }}
                            className="rounded-md bg-indigo-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-400"
                        >
                            Descargar Excel
                        </button>
                        <button
                            onClick={() => setSelected(new Set())}
                            className="text-sm text-gray-400 hover:text-white"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            )}
            {deleteTarget !== null && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div className="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                                <svg className="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </div>
                            <h3 className="text-base font-semibold text-gray-900">Eliminar factura</h3>
                        </div>
                        <p className="text-sm text-gray-600">
                            ¿Estás seguro de que quieres eliminar <span className="font-medium">{deleteTarget.file_name}</span>?
                        </p>
                        <p className="mt-1 text-sm text-gray-500">Esta acción no se puede deshacer.</p>
                        <div className="mt-5 flex justify-end gap-3">
                            <button
                                onClick={() => setDeleteTarget(null)}
                                className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                            <button
                                onClick={() => {
                                    router.delete(route('invoices.destroy', deleteTarget.id));
                                    setDeleteTarget(null);
                                }}
                                className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                            >
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {exportWarning !== null && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div className="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                                <svg className="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                            </div>
                            <h3 className="text-base font-semibold text-gray-900">No se puede exportar</h3>
                        </div>
                        <p className="text-sm text-gray-600">
                            {exportWarning === 1
                                ? 'Hay 1 factura seleccionada que no está validada.'
                                : `Hay ${exportWarning} facturas seleccionadas que no están validadas.`}
                        </p>
                        <p className="mt-1 text-sm text-gray-500">
                            Solo se pueden exportar a SAGE facturas con estado <span className="font-medium text-green-700">Validada</span>.
                        </p>
                        <div className="mt-5 flex justify-end">
                            <button
                                onClick={() => setExportWarning(null)}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                Entendido
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}

const thCls = 'px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500';
const tdCls = 'whitespace-nowrap px-4 py-3 text-sm text-gray-700';
const selectCls = 'block w-full rounded-md border border-gray-300 px-2.5 py-1.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400';
