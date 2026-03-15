import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type PageProps } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useCallback, useRef, useState } from 'react';

const MAX_FILES = 20;
const MAX_SIZE_MB = 10;
const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

interface FileError {
    name: string;
    message: string;
}

export default function Upload(_props: PageProps) {
    const [type, setType] = useState<'received' | 'issued' | null>(null);
    const [files, setFiles] = useState<File[]>([]);
    const [errors, setErrors] = useState<FileError[]>([]);
    const [dragging, setDragging] = useState(false);
    const [uploading, setUploading] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    const validate = (incoming: File[]): { valid: File[]; errs: FileError[] } => {
        const errs: FileError[] = [];
        const valid: File[] = [];

        incoming.forEach((f) => {
            if (f.type !== 'application/pdf') {
                errs.push({ name: f.name, message: 'Solo se admiten archivos PDF.' });
            } else if (f.size > MAX_SIZE_BYTES) {
                errs.push({ name: f.name, message: `Supera el tamaño máximo de ${MAX_SIZE_MB} MB.` });
            } else {
                valid.push(f);
            }
        });

        return { valid, errs };
    };

    const addFiles = useCallback(
        (incoming: File[]) => {
            const { valid, errs } = validate(incoming);
            const combined = [...files, ...valid];

            if (combined.length > MAX_FILES) {
                setErrors([
                    ...errs,
                    { name: '', message: `Máximo ${MAX_FILES} archivos por subida.` },
                ]);
                setFiles(combined.slice(0, MAX_FILES));
            } else {
                setErrors(errs);
                setFiles(combined);
            }
        },
        [files],
    );

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setDragging(false);
        addFiles(Array.from(e.dataTransfer.files));
    };

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) addFiles(Array.from(e.target.files));
        e.target.value = '';
    };

    const removeFile = (index: number) => setFiles(files.filter((_, i) => i !== index));

    const submit = () => {
        if (files.length === 0 || uploading || !type) return;
        setUploading(true);

        const formData = new FormData();
        formData.append('type', type);
        files.forEach((f) => formData.append('files[]', f));

        router.post(route('invoices.upload.store'), formData, {
            onError: () => setUploading(false),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Subir facturas
                </h2>
            }
        >
            <Head title="Subir facturas" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8 space-y-6">

                    {/* Tipo de factura */}
                    <div className="rounded-lg bg-white p-6 shadow-sm">
                        <p className="mb-3 text-sm font-medium text-gray-700">¿Qué tipo de facturas vas a subir?</p>
                        <div className="flex gap-3">
                            <button
                                onClick={() => setType('received')}
                                className={`flex-1 rounded-lg border-2 px-4 py-3 text-sm font-medium transition ${
                                    type === 'received'
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                        : 'border-gray-200 text-gray-600 hover:border-indigo-300 hover:bg-gray-50'
                                }`}
                            >
                                <span className="block text-base">📥</span>
                                Recibidas
                                <span className="mt-0.5 block text-xs font-normal text-gray-400">Facturas de proveedores</span>
                            </button>
                            <button
                                onClick={() => setType('issued')}
                                className={`flex-1 rounded-lg border-2 px-4 py-3 text-sm font-medium transition ${
                                    type === 'issued'
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                        : 'border-gray-200 text-gray-600 hover:border-indigo-300 hover:bg-gray-50'
                                }`}
                            >
                                <span className="block text-base">📤</span>
                                Emitidas
                                <span className="mt-0.5 block text-xs font-normal text-gray-400">Facturas a clientes</span>
                            </button>
                        </div>
                    </div>

                    {/* Drop zone — solo visible si se ha elegido tipo */}
                    {type && (
                        <div
                            onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
                            onDragLeave={() => setDragging(false)}
                            onDrop={handleDrop}
                            onClick={() => inputRef.current?.click()}
                            className={`flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed p-12 transition ${
                                dragging
                                    ? 'border-indigo-500 bg-indigo-50'
                                    : 'border-gray-300 bg-white hover:border-indigo-400 hover:bg-gray-50'
                            }`}
                        >
                            <svg className="mb-3 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p className="text-sm font-medium text-gray-700">
                                Arrastra los PDFs aquí o{' '}
                                <span className="text-indigo-600">haz clic para seleccionar</span>
                            </p>
                            <p className="mt-1 text-xs text-gray-500">
                                Solo PDF · máx. {MAX_SIZE_MB} MB por archivo · máx. {MAX_FILES} archivos
                            </p>
                            <input
                                ref={inputRef}
                                type="file"
                                multiple
                                accept="application/pdf"
                                className="hidden"
                                onChange={handleChange}
                            />
                        </div>
                    )}

                    {/* Validation errors */}
                    {errors.length > 0 && (
                        <ul className="rounded-md bg-red-50 p-4 text-sm text-red-700 space-y-1">
                            {errors.map((e, i) => (
                                <li key={i}>{e.name ? <strong>{e.name}:</strong> : null} {e.message}</li>
                            ))}
                        </ul>
                    )}

                    {/* File list */}
                    {files.length > 0 && (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                                <span className="text-sm font-medium text-gray-700">
                                    {files.length} {files.length === 1 ? 'archivo' : 'archivos'} seleccionados
                                </span>
                                <button
                                    onClick={() => setFiles([])}
                                    className="text-sm text-red-500 hover:text-red-700"
                                >
                                    Limpiar todo
                                </button>
                            </div>
                            <ul className="divide-y divide-gray-100">
                                {files.map((f, i) => (
                                    <li key={i} className="flex items-center justify-between px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <svg className="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clipRule="evenodd" />
                                            </svg>
                                            <span className="text-sm text-gray-700">{f.name}</span>
                                            <span className="text-xs text-gray-400">
                                                {(f.size / 1024 / 1024).toFixed(2)} MB
                                            </span>
                                        </div>
                                        <button
                                            onClick={() => removeFile(i)}
                                            className="text-gray-400 hover:text-red-500"
                                        >
                                            ✕
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {/* Submit */}
                    <div className="flex justify-end">
                        <button
                            onClick={submit}
                            disabled={files.length === 0 || uploading || !type}
                            className="rounded-md bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {uploading ? 'Subiendo...' : `Subir ${files.length > 0 ? files.length : ''} factura${files.length !== 1 ? 's' : ''}`}
                        </button>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
