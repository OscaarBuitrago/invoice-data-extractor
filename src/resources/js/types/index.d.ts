export type UserRole = 'superadmin' | 'admin' | 'consultant';

export interface ClientCompany {
    id: string;
    name: string;
    tax_id: string;
    active: boolean;
    consultancy_id: string;
}

export interface Consultancy {
    id: string;
    name: string;
    tax_id: string;
    active: boolean;
    created_at: string;
    users?: User[];
}

export interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at?: string;
    role: UserRole;
    consultancy_id: string | null;
    consultancy?: Consultancy;
}

export interface ImportResult {
    created: { name: string; tax_id: string }[];
    skipped: { name: string; tax_id: string }[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    activeCompany: Pick<ClientCompany, 'id' | 'name' | 'tax_id'> | null;
    flash: {
        import_result?: ImportResult;
    };
};

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}
