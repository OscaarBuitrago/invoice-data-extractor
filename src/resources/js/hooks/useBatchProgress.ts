import { useEffect, useRef, useState } from 'react';

export type BatchStatus = 'processing' | 'completed' | 'with_errors';

export interface BatchProgress {
    id: string;
    status: BatchStatus;
    total_invoices: number;
    processed_invoices: number;
    duplicate_files: string[];
}

export function useBatchProgress(batchId: string, initialStatus: BatchStatus) {
    const [progress, setProgress] = useState<BatchProgress | null>(null);
    const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

    const isDone = (status: BatchStatus) =>
        status === 'completed' || status === 'with_errors';

    useEffect(() => {
        if (isDone(initialStatus)) return;

        const poll = async () => {
            try {
                const res = await fetch(route('invoices.batches.status', batchId), {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) return;
                const data: BatchProgress = await res.json();
                setProgress(data);

                if (isDone(data.status) && intervalRef.current) {
                    clearInterval(intervalRef.current);
                }
            } catch {
                // silently ignore network errors during polling
            }
        };

        poll();
        intervalRef.current = setInterval(poll, 2000);

        return () => {
            if (intervalRef.current) clearInterval(intervalRef.current);
        };
    }, [batchId, initialStatus]);

    return progress;
}
