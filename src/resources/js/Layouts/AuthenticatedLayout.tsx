import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, activeCompany } = usePage().props;
    const user = auth.user;
    const isSuperAdmin = user.role === 'superadmin';

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="border-b border-gray-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href={route(isSuperAdmin ? 'consultancies.index' : 'invoices.index')}>
                                    <img src="/logo.png" alt="Logo" className="h-20 w-auto" />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                {isSuperAdmin ? (
                                    <>
                                        <NavLink
                                            href={route('consultancies.index')}
                                            active={route().current('consultancies.*')}
                                        >
                                            Asesorías
                                        </NavLink>
                                        <NavLink
                                            href={route('users.index')}
                                            active={route().current('users.*')}
                                        >
                                            Usuarios
                                        </NavLink>
                                    </>
                                ) : (
                                    <>
                                        <NavLink
                                            href={route('invoices.index')}
                                            active={route().current('invoices.index')}
                                        >
                                            Facturas
                                        </NavLink>
                                        {user.role === 'admin' && (
                                            <NavLink
                                                href={route('client-companies.index')}
                                                active={route().current('client-companies.*')}
                                            >
                                                Empresas
                                            </NavLink>
                                        )}
                                    </>
                                )}
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center gap-4">
                            {!isSuperAdmin && activeCompany && (
                                <Link
                                    href={route('context.select')}
                                    className="flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1.5 text-sm text-indigo-700 hover:bg-indigo-100"
                                >
                                    <span className="h-2 w-2 rounded-full bg-indigo-500" />
                                    <span className="font-medium">{activeCompany.name}</span>
                                    <span className="text-indigo-400">·</span>
                                    <span className="text-xs text-indigo-500">{activeCompany.tax_id}</span>
                                </Link>
                            )}
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {user.name}

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                        >
                                            Profile
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        {isSuperAdmin ? (
                            <>
                                <ResponsiveNavLink
                                    href={route('consultancies.index')}
                                    active={route().current('consultancies.*')}
                                >
                                    Asesorías
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('users.index')}
                                    active={route().current('users.*')}
                                >
                                    Usuarios
                                </ResponsiveNavLink>
                            </>
                        ) : (
                            <>
                                <ResponsiveNavLink
                                    href={route('invoices.index')}
                                    active={route().current('invoices.index')}
                                >
                                    Facturas
                                </ResponsiveNavLink>
                                {user.role === 'admin' && (
                                    <ResponsiveNavLink
                                        href={route('client-companies.index')}
                                        active={route().current('client-companies.*')}
                                    >
                                        Empresas
                                    </ResponsiveNavLink>
                                )}
                            </>
                        )}
                    </div>

                    <div className="border-t border-gray-200 pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-medium text-gray-800">
                                {user.name}
                            </div>
                            <div className="text-sm font-medium text-gray-500">
                                {user.email}
                            </div>
                            {!isSuperAdmin && activeCompany && (
                                <div className="mt-1 text-sm font-medium text-indigo-600">
                                    {activeCompany.name}
                                </div>
                            )}
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
