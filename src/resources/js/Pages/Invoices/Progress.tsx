import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type PageProps } from '@/types';
import { type BatchStatus, useBatchProgress } from '@/hooks/useBatchProgress';
import { Head, Link } from '@inertiajs/react';

interface Batch {
    id: string;
    status: BatchStatus;
    total_invoices: number;
    processed_invoices: number;
    duplicate_files: string[];
}

interface Props extends PageProps {
    batch: Batch;
    firstInvoiceId: string | null;
}

const STATUS_LABEL: Record<BatchStatus, string> = {
    processing: 'Procesando...',
    completed: 'Completado',
    with_errors: 'Completado con errores',
};

const STATUS_COLOR: Record<BatchStatus, string> = {
    processing: 'bg-blue-500',
    completed: 'bg-green-500',
    with_errors: 'bg-yellow-500',
};

export default function Progress({ batch: initialBatch, firstInvoiceId }: Props) {
    const polled = useBatchProgress(initialBatch.id, initialBatch.status);
    const batch = polled ?? { ...initialBatch, duplicate_files: initialBatch.duplicate_files ?? [] };

    const percentage =
        batch.total_invoices > 0
            ? Math.round((batch.processed_invoices / batch.total_invoices) * 100)
            : 0;

    const isDone = batch.status === 'completed' || batch.status === 'with_errors';

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Procesando facturas
                </h2>
            }
        >
            <Head title="Procesando facturas" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-8 shadow-sm sm:rounded-lg space-y-6">

                        {/* Status badge */}
                        <div className="flex items-center gap-3">
                            <span
                                className={`inline-flex h-3 w-3 rounded-full ${STATUS_COLOR[batch.status]} ${
                                    batch.status === 'processing' ? 'animate-pulse' : ''
                                }`}
                            />
                            <span className="text-sm font-medium text-gray-700">
                                {STATUS_LABEL[batch.status]}
                            </span>
                        </div>

                        {/* Progress bar */}
                        <div>
                            <div className="mb-2 flex justify-between text-sm text-gray-600">
                                <span>
                                    {batch.processed_invoices} de {batch.total_invoices} facturas procesadas
                                </span>
                                <span>{percentage}%</span>
                            </div>
                            <div className="h-3 w-full overflow-hidden rounded-full bg-gray-200">
                                <div
                                    className={`h-3 rounded-full transition-all duration-500 ${STATUS_COLOR[batch.status]}`}
                                    style={{ width: `${percentage}%` }}
                                />
                            </div>
                        </div>

                        {/* Actions */}
                        {isDone && (
                            <div className="space-y-3 pt-2">
                                {batch.duplicate_files.length > 0 && (
                                    <div className="rounded-lg border border-orange-200 bg-orange-50 p-3">
                                        <p className="text-sm font-medium text-orange-800">
                                            {batch.duplicate_files.length === 1
                                                ? 'La siguiente factura ya existe y no ha sido procesada:'
                                                : `Las siguientes ${batch.duplicate_files.length} facturas ya existen y no han sido procesadas:`}
                                        </p>
                                        <ul className="mt-2 space-y-1">
                                            {batch.duplicate_files.map((name) => (
                                                <li key={name} className="flex items-center gap-2 text-sm text-orange-700">
                                                    <svg className="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 17.25 12 21m0 0-3.75-3.75M12 21V3" />
                                                    </svg>
                                                    {name}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                                {firstInvoiceId ? (
                                    <Link
                                        href={route('invoices.show', firstInvoiceId)}
                                        className="block w-full rounded-md bg-indigo-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-indigo-700"
                                    >
                                        Revisar facturas →
                                    </Link>
                                ) : (
                                    <Link
                                        href={route('invoices.index')}
                                        className="block w-full rounded-md bg-indigo-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-indigo-700"
                                    >
                                        Ver listado
                                    </Link>
                                )}
                            </div>
                        )}

                        {!isDone && (
                            <p className="text-xs text-gray-400">
                                Esta página se actualiza automáticamente. No la cierres hasta que finalice.
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
