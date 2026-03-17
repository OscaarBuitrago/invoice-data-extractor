import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type Consultancy, type PageProps, type PaginatedData } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

interface Props extends PageProps {
    consultancies: PaginatedData<Consultancy>;
}

export default function ConsultanciesIndex({ consultancies }: Props) {
    const [expandedId, setExpandedId] = useState<string | null>(null);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Asesorías
                    </h2>
                    <div className="flex gap-2">
                        <Link
                            href={route('users.create')}
                            className="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                        >
                            Añadir usuario
                        </Link>
                        <Link
                            href={route('consultancies.create')}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Nueva asesoría
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Asesorías" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="w-8 px-4 py-3" />
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Nombre
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        CIF
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Estado
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Admins
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Consultores
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {consultancies.data.map((consultancy) => {
                                    const users = consultancy.users ?? [];
                                    const admins = users.filter((u) => u.role === 'admin');
                                    const consultants = users.filter((u) => u.role === 'consultant');
                                    const isExpanded = expandedId === consultancy.id;

                                    return (
                                        <>
                                            <tr
                                                key={consultancy.id}
                                                className="cursor-pointer hover:bg-gray-50"
                                                onClick={() =>
                                                    setExpandedId(isExpanded ? null : consultancy.id)
                                                }
                                            >
                                                <td className="px-4 py-4 text-gray-400">
                                                    <svg
                                                        className={`h-4 w-4 transition-transform ${isExpanded ? 'rotate-90' : ''}`}
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M9 5l7 7-7 7"
                                                        />
                                                    </svg>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                                    {consultancy.name}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                    {consultancy.tax_id}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                    <span
                                                        className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${
                                                            consultancy.active
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-red-100 text-red-800'
                                                        }`}
                                                    >
                                                        {consultancy.active ? 'Activa' : 'Inactiva'}
                                                    </span>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                    {admins.length}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                    {consultants.length}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                    <Link
                                                        href={route('users.create', { consultancy_id: consultancy.id })}
                                                        onClick={(e) => e.stopPropagation()}
                                                        className="text-indigo-600 hover:text-indigo-900"
                                                    >
                                                        Añadir usuario
                                                    </Link>
                                                </td>
                                            </tr>

                                            {isExpanded && (
                                                <tr key={`${consultancy.id}-expanded`}>
                                                    <td colSpan={7} className="bg-gray-50 px-10 py-4">
                                                        {users.length === 0 ? (
                                                            <p className="text-sm text-gray-500">
                                                                Sin usuarios
                                                            </p>
                                                        ) : (
                                                            <table className="min-w-full">
                                                                <thead>
                                                                    <tr>
                                                                        <th className="pb-2 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                                                                            Nombre
                                                                        </th>
                                                                        <th className="pb-2 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                                                                            Email
                                                                        </th>
                                                                        <th className="pb-2 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                                                                            Rol
                                                                        </th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody className="divide-y divide-gray-100">
                                                                    {users.map((u) => (
                                                                        <tr key={u.id}>
                                                                            <td className="py-2 text-sm font-medium text-gray-900">
                                                                                {u.name}
                                                                            </td>
                                                                            <td className="py-2 text-sm text-gray-500">
                                                                                {u.email}
                                                                            </td>
                                                                            <td className="py-2 text-sm">
                                                                                <span
                                                                                    className={`inline-flex rounded-full px-2 text-xs font-semibold leading-5 ${
                                                                                        u.role === 'admin'
                                                                                            ? 'bg-green-100 text-green-800'
                                                                                            : 'bg-blue-100 text-blue-800'
                                                                                    }`}
                                                                                >
                                                                                    {u.role === 'admin' ? 'Admin' : 'Consultor'}
                                                                                </span>
                                                                            </td>
                                                                        </tr>
                                                                    ))}
                                                                </tbody>
                                                            </table>
                                                        )}
                                                    </td>
                                                </tr>
                                            )}
                                        </>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
