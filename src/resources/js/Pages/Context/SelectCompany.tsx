import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type ClientCompany, type PageProps } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

interface Props extends PageProps {
    companies: ClientCompany[];
}

export default function SelectCompany({ companies }: Props) {
    const [search, setSearch] = useState('');
    const { data, setData, post, processing } = useForm({
        client_company_id: '',
    });

    const filtered = companies.filter(
        (c) =>
            c.name.toLowerCase().includes(search.toLowerCase()) ||
            c.tax_id.toLowerCase().includes(search.toLowerCase()),
    );

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('context.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Seleccionar empresa
                </h2>
            }
        >
            <Head title="Seleccionar empresa" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <p className="mb-6 text-sm text-gray-600">
                            Selecciona la empresa con la que deseas trabajar en esta sesión.
                        </p>

                        <form onSubmit={submit} className="space-y-4">
                            <input
                                type="text"
                                placeholder="Buscar por nombre o CIF..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />

                            <div className="max-h-80 overflow-y-auto rounded-md border border-gray-200">
                                {filtered.length === 0 ? (
                                    <p className="px-4 py-6 text-center text-sm text-gray-500">
                                        No se encontraron empresas.
                                    </p>
                                ) : (
                                    filtered.map((company) => (
                                        <label
                                            key={company.id}
                                            className={`flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-50 ${
                                                data.client_company_id === company.id
                                                    ? 'bg-indigo-50'
                                                    : ''
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="client_company_id"
                                                value={company.id}
                                                checked={data.client_company_id === company.id}
                                                onChange={() =>
                                                    setData('client_company_id', company.id)
                                                }
                                                className="text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">
                                                    {company.name}
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    {company.tax_id}
                                                </p>
                                            </div>
                                        </label>
                                    ))
                                )}
                            </div>

                            <PrimaryButton
                                disabled={processing || !data.client_company_id}
                                className="w-full justify-center"
                            >
                                Acceder
                            </PrimaryButton>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
