import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type ClientCompany, type ImportResult, type PageProps, type PaginatedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

interface Props extends PageProps {
    clientCompanies: PaginatedData<ClientCompany>;
}

export default function ClientCompaniesIndex({ clientCompanies }: Props) {
    const { flash } = usePage<PageProps>().props;

    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [importResult, setImportResult] = useState<ImportResult | null>(null);

    useEffect(() => {
        if (flash.import_result) {
            setImportResult(flash.import_result);
        }
    }, [flash.import_result]);

    const fileInputRef = useRef<HTMLInputElement>(null);

    // Quick create form
    const createForm = useForm({ name: '', tax_id: '' });

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(route('client-companies.store'), {
            onSuccess: () => {
                setShowCreateModal(false);
                createForm.reset();
            },
        });
    };

    // Import form
    const importForm = useForm<{ file: File | null }>({ file: null });

    const submitImport = (e: React.FormEvent) => {
        e.preventDefault();
        importForm.post(route('client-companies.import'), {
            forceFormData: true,
            onSuccess: () => {
                setShowImportModal(false);
                importForm.reset();
                if (fileInputRef.current) fileInputRef.current.value = '';
                // flash.import_result is now in the new page props
            },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Empresas cliente
                    </h2>
                    <div className="flex gap-2">
                        <SecondaryButton onClick={() => setShowImportModal(true)}>
                            Importar Excel
                        </SecondaryButton>
                        <PrimaryButton onClick={() => setShowCreateModal(true)}>
                            Nueva empresa
                        </PrimaryButton>
                    </div>
                </div>
            }
        >
            <Head title="Empresas cliente" />

            {/* Import result banner */}
            {importResult && (
                <div className="bg-amber-50 border-b border-amber-200 px-4 py-3">
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex-1">
                                {importResult.created.length > 0 && (
                                    <p className="text-sm text-green-700 font-medium">
                                        {importResult.created.length} empresa{importResult.created.length !== 1 ? 's' : ''} importada{importResult.created.length !== 1 ? 's' : ''} correctamente.
                                    </p>
                                )}
                                {importResult.skipped.length > 0 && (
                                    <div className="mt-1">
                                        <p className="text-sm text-amber-800 font-medium">
                                            {importResult.skipped.length} empresa{importResult.skipped.length !== 1 ? 's' : ''} no se {importResult.skipped.length !== 1 ? 'han podido importar' : 'ha podido importar'} porque su CIF ya existe en el sistema:
                                        </p>
                                        <ul className="mt-1 list-disc list-inside space-y-0.5">
                                            {importResult.skipped.map((s) => (
                                                <li key={s.tax_id} className="text-sm text-amber-700">
                                                    <span className="font-medium">{s.name}</span>{' '}
                                                    <span className="text-amber-500">({s.tax_id})</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                            <button
                                onClick={() => setImportResult(null)}
                                className="text-amber-400 hover:text-amber-600 text-lg leading-none"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                </div>
            )}

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Nombre
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        CIF
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {clientCompanies.data.map((company) => (
                                    <tr key={company.id}>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {company.name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {company.tax_id}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            <span
                                                className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${
                                                    company.active
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-red-100 text-red-800'
                                                }`}
                                            >
                                                {company.active ? 'Activa' : 'Inactiva'}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Quick create modal */}
            <Modal
                show={showCreateModal}
                maxWidth="md"
                onClose={() => {
                    setShowCreateModal(false);
                    createForm.reset();
                    createForm.clearErrors();
                }}
            >
                <form onSubmit={submitCreate} className="p-6 space-y-5">
                    <h3 className="text-lg font-medium text-gray-900">Nueva empresa cliente</h3>

                    <div>
                        <InputLabel htmlFor="create-name" value="Nombre" />
                        <TextInput
                            id="create-name"
                            value={createForm.data.name}
                            onChange={(e) => createForm.setData('name', e.target.value)}
                            className="mt-1 block w-full"
                            autoFocus
                        />
                        <InputError message={createForm.errors.name} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="create-tax_id" value="CIF / NIF" />
                        <TextInput
                            id="create-tax_id"
                            value={createForm.data.tax_id}
                            onChange={(e) => createForm.setData('tax_id', e.target.value)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={createForm.errors.tax_id} className="mt-2" />
                    </div>

                    <div className="flex justify-end gap-3">
                        <SecondaryButton
                            type="button"
                            onClick={() => {
                                setShowCreateModal(false);
                                createForm.reset();
                                createForm.clearErrors();
                            }}
                        >
                            Cancelar
                        </SecondaryButton>
                        <PrimaryButton disabled={createForm.processing}>
                            Crear empresa
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Import modal */}
            <Modal
                show={showImportModal}
                maxWidth="md"
                onClose={() => {
                    setShowImportModal(false);
                    importForm.reset();
                    importForm.clearErrors();
                    if (fileInputRef.current) fileInputRef.current.value = '';
                }}
            >
                <form onSubmit={submitImport} className="p-6 space-y-5">
                    <div>
                        <h3 className="text-lg font-medium text-gray-900">Importar desde Excel</h3>
                        <p className="mt-1 text-sm text-gray-500">
                            El archivo debe tener el nombre en la columna A y el CIF en la columna B.
                            Las empresas con CIF duplicado se omitirán y se te informará al finalizar.
                        </p>
                    </div>

                    <div>
                        <InputLabel htmlFor="import-file" value="Archivo (.xlsx, .xls, .csv)" />
                        <input
                            id="import-file"
                            ref={fileInputRef}
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            onChange={(e) =>
                                importForm.setData('file', e.target.files?.[0] ?? null)
                            }
                            className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                        />
                        <InputError message={importForm.errors.file} className="mt-2" />
                    </div>

                    <div className="flex justify-end gap-3">
                        <SecondaryButton
                            type="button"
                            onClick={() => {
                                setShowImportModal(false);
                                importForm.reset();
                                importForm.clearErrors();
                                if (fileInputRef.current) fileInputRef.current.value = '';
                            }}
                        >
                            Cancelar
                        </SecondaryButton>
                        <PrimaryButton disabled={importForm.processing || !importForm.data.file}>
                            {importForm.processing ? 'Importando…' : 'Importar'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
