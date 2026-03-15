import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type PageProps } from '@/types';
import { type BatchStatus, useBatchProgress } from '@/hooks/useBatchProgress';
import { Head, Link } from '@inertiajs/react';

interface Batch {
    id: string;
    status: BatchStatus;
    total_invoices: number;
    processed_invoices: number;
}

interface Props extends PageProps {
    batch: Batch;
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

export default function Progress({ batch: initialBatch }: Props) {
    const polled = useBatchProgress(initialBatch.id, initialBatch.status);
    const batch = polled ?? initialBatch;

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
                            <div className="flex gap-3 pt-2">
                                {batch.status === 'with_errors' && (
                                    <p className="text-sm text-yellow-700">
                                        Algunas facturas no pudieron procesarse. Puedes revisarlas en el listado.
                                    </p>
                                )}
                                <Link
                                    href={route('dashboard')}
                                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                >
                                    Ir al inicio
                                </Link>
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
