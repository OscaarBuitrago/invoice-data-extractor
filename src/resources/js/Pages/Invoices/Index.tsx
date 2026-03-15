import ConfidenceBadge from '@/Components/Invoices/ConfidenceBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type PageProps, type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface Invoice {
    id: string;
    file_name: string;
    invoice_date: string | null;
    invoice_number: string | null;
    issuer_name: string | null;
    issuer_tax_id: string | null;
    taxable_base: number | null;
    vat_amount: number | null;
    irpf_amount: number | null;
    total: number | null;
    type: string;
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

export default function InvoicesIndex({ invoices, filters }: Props) {
    const [selected, setSelected] = useState<Set<string>>(new Set());
    const [localFilters, setLocalFilters] = useState(filters);

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

    const applyFilters = () => {
        router.get(
            route('invoices.index'),
            Object.fromEntries(Object.entries(localFilters).filter(([, v]) => v !== null && v !== '')),
            { preserveState: true, replace: true },
        );
    };

    const clearFilters = () => {
        const empty = { type: null, validation_status: null, operation_type: null, date_from: null, date_to: null, exported_to_sage: null };
        setLocalFilters(empty);
        router.get(route('invoices.index'), {}, { replace: true });
    };

    const hasFilters = Object.values(localFilters).some((v) => v !== null && v !== '');

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Facturas</h2>
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
                            <select
                                value={localFilters.type ?? ''}
                                onChange={(e) => setLocalFilters({ ...localFilters, type: e.target.value || null })}
                                className={selectCls}
                            >
                                <option value="">Tipo</option>
                                <option value="received">Recibida</option>
                                <option value="issued">Emitida</option>
                            </select>

                            <select
                                value={localFilters.validation_status ?? ''}
                                onChange={(e) => setLocalFilters({ ...localFilters, validation_status: e.target.value || null })}
                                className={selectCls}
                            >
                                <option value="">Estado</option>
                                <option value="pending">Pendiente</option>
                                <option value="validated">Validada</option>
                                <option value="rejected">Rechazada</option>
                            </select>

                            <select
                                value={localFilters.operation_type ?? ''}
                                onChange={(e) => setLocalFilters({ ...localFilters, operation_type: e.target.value || null })}
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
                                onChange={(e) => setLocalFilters({ ...localFilters, date_from: e.target.value || null })}
                                className={selectCls}
                                placeholder="Desde"
                            />

                            <input
                                type="date"
                                value={localFilters.date_to ?? ''}
                                onChange={(e) => setLocalFilters({ ...localFilters, date_to: e.target.value || null })}
                                className={selectCls}
                                placeholder="Hasta"
                            />

                            <select
                                value={localFilters.exported_to_sage ?? ''}
                                onChange={(e) => setLocalFilters({ ...localFilters, exported_to_sage: e.target.value || null })}
                                className={selectCls}
                            >
                                <option value="">SAGE</option>
                                <option value="1">Exportada</option>
                                <option value="0">No exportada</option>
                            </select>
                        </div>

                        <div className="mt-3 flex gap-2">
                            <button onClick={applyFilters} className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                                Filtrar
                            </button>
                            {hasFilters && (
                                <button onClick={clearFilters} className="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50">
                                    Limpiar
                                </button>
                            )}
                        </div>
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
                                    <th className={thCls}>Emisor</th>
                                    <th className={thCls}>Base imp.</th>
                                    <th className={thCls}>IVA</th>
                                    <th className={thCls}>IRPF</th>
                                    <th className={thCls}>Total</th>
                                    <th className={thCls}>Confianza</th>
                                    <th className={thCls}>Estado</th>
                                    <th className={thCls}></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {invoices.data.length === 0 && (
                                    <tr>
                                        <td colSpan={11} className="px-6 py-10 text-center text-sm text-gray-400">
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
                                            <input
                                                type="checkbox"
                                                checked={selected.has(invoice.id)}
                                                onChange={() => toggle(invoice.id)}
                                                className="rounded border-gray-300 text-indigo-600"
                                            />
                                        </td>
                                        <td className={tdCls}>{invoice.invoice_date ?? '—'}</td>
                                        <td className={tdCls}>{invoice.invoice_number ?? '—'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-700">
                                            <div className="font-medium">{invoice.issuer_name ?? '—'}</div>
                                            <div className="text-xs text-gray-400">{invoice.issuer_tax_id}</div>
                                        </td>
                                        <td className={`${tdCls} text-right`}>{fmt(invoice.taxable_base)}</td>
                                        <td className={`${tdCls} text-right`}>{fmt(invoice.vat_amount)}</td>
                                        <td className={`${tdCls} text-right`}>{invoice.irpf_amount ? fmt(invoice.irpf_amount) : '—'}</td>
                                        <td className={`${tdCls} text-right font-medium`}>{fmt(invoice.total)}</td>
                                        <td className="px-4 py-3">
                                            <ConfidenceBadge confidence={invoice.ocr_confidence} />
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${VALIDATION_STYLES[invoice.validation_status]}`}>
                                                {VALIDATION_LABELS[invoice.validation_status]}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={route('invoices.show', invoice.id)}
                                                className="text-sm text-indigo-600 hover:text-indigo-800"
                                            >
                                                Revisar
                                            </Link>
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
                            onClick={() => setSelected(new Set())}
                            className="text-sm text-gray-400 hover:text-white"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}

const thCls = 'px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500';
const tdCls = 'whitespace-nowrap px-4 py-3 text-sm text-gray-700';
const selectCls = 'block w-full rounded-md border border-gray-300 px-2.5 py-1.5 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400';
