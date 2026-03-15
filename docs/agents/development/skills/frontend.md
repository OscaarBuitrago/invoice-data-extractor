# Frontend Skill: React + TypeScript + Tailwind + Inertia

## Overview

Guide for implementing frontend features in APPCC using React 19, TypeScript 5.9, Tailwind CSS v4, and Inertia.js v2.

Frontend features follow the same **TDD discipline** as backend: tests are written first, then implementation follows.

---

## Stack Reference

| Package | Version | Purpose |
|---------|---------|---------|
| `react` | 19.2 | UI framework |
| `typescript` | 5.9 | Type safety |
| `@inertiajs/react` | 2.3 | Inertia integration |
| `tailwindcss` | 4.1 | Styling |
| `@radix-ui/*` | Latest | Accessible UI primitives |
| `lucide-react` | 0.574 | Icons |
| `vitest` | Latest | Unit/component testing |

---

## Project Structure

```
resources/js/
├── __tests__/              ← Unit & component tests
├── actions/                ← State management (if needed)
├── components/             ← Reusable components
│   ├── ui/                 ← Radix UI components
│   ├── sidebar/            ← Layout components
│   └── [...other domains]
├── hooks/                  ← Custom React hooks
├── layouts/                ← Page layouts
├── lib/                    ← Utilities (API, classnames, etc.)
├── pages/                  ← Inertia pages
├── routes/                 ← Route definitions (from Wayfinder)
├── types/                  ← TypeScript interfaces
├── app.tsx                 ← Entry point
└── ssr.tsx                 ← SSR entry (if enabled)
```

---

## Component Structure

### Basic Component (Functional)

```typescript
// resources/js/components/Button.tsx

interface ButtonProps {
    children: React.ReactNode;
    onClick?: () => void;
    variant?: 'primary' | 'secondary';
    disabled?: boolean;
    className?: string;
}

export default function Button({
    children,
    onClick,
    variant = 'primary',
    disabled = false,
    className = '',
}: ButtonProps) {
    const baseStyles = 'px-4 py-2 rounded font-medium transition-colors';
    const variantStyles = {
        primary: 'bg-blue-600 text-white hover:bg-blue-700',
        secondary: 'bg-gray-200 text-gray-900 hover:bg-gray-300',
    };

    return (
        <button
            onClick={onClick}
            disabled={disabled}
            className={`${baseStyles} ${variantStyles[variant]} ${className} ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
        >
            {children}
        </button>
    );
}
```

### Props Pattern

**Rules:**
- Always use a `Props` interface named `{ComponentName}Props`
- Place interface above the component function
- Destructure props in function signature
- Make optional props explicit with `?:`
- Use `React.ReactNode` for children, not `any`
- Include `className?: string` for composition

```typescript
interface CardProps {
    title: string;
    description?: string;
    children?: React.ReactNode;
    variant?: 'default' | 'elevated';
    className?: string;
}

export default function Card({
    title,
    description,
    children,
    variant = 'default',
    className = '',
}: CardProps) {
    // ...
}
```

---

## Naming Conventions

### Files
- **Components:** PascalCase with `.tsx` extension
  - ✅ `UserProfile.tsx`, `SidebarMenu.tsx`, `ChecklistForm.tsx`
  - ❌ `userProfile.tsx`, `sidebar_menu.tsx`

- **Pages (Inertia):** PascalCase with `.tsx`, grouped by domain
  - ✅ `resources/js/pages/Checklists/Index.tsx`
  - ✅ `resources/js/pages/Executions/Show.tsx`

- **Hooks:** camelCase, start with `use`
  - ✅ `useForm.ts`, `useLocalStorage.ts`
  - ❌ `form.ts`, `useFormHook.ts`

- **Types/Interfaces:** PascalCase
  - ✅ `User.ts`, `ChecklistResponse.ts`
  - ❌ `user.ts`, `checklist-response.ts`

- **Utilities:** camelCase, descriptive
  - ✅ `classNames.ts`, `formatDate.ts`
  - ❌ `utils.ts`, `helpers.ts`

### Variables & Functions
- **Constants:** UPPER_SNAKE_CASE
  - ✅ `const MODAL_TIMEOUT = 300;`
  - ❌ `const modalTimeout = 300;`

- **Functions & variables:** camelCase
  - ✅ `const getUser = () => {...}`
  - ❌ `const GetUser = () => {...}`

- **Boolean variables:** prefix with `is`, `has`, `can`
  - ✅ `isOpen`, `hasError`, `canEdit`
  - ❌ `open`, `error`, `edit`

---

## TypeScript Patterns

### Strict Types Required

**All parameters and returns must have explicit types:**

```typescript
// ❌ WRONG
function formatDate(date) {
    return date.toLocaleDateString();
}

// ✅ CORRECT
function formatDate(date: Date): string {
    return date.toLocaleDateString();
}
```

### Component Props

```typescript
interface UserCardProps {
    userId: string;
    onEdit?: (userId: string) => void;
}

export default function UserCard({ userId, onEdit }: UserCardProps): React.ReactElement {
    return <div>...</div>;
}
```

### API Response Types

Define types for API responses from Wayfinder:

```typescript
// resources/js/types/User.ts

export interface User {
    id: string;
    name: string;
    email: string;
    role: 'owner' | 'manager' | 'operator';
    createdAt: string;
}

export interface UserResponse {
    data: User;
}

export interface UsersListResponse {
    data: User[];
    meta?: {
        total: number;
        page: number;
    };
}
```

### Generic Components

```typescript
interface ListProps<T> {
    items: T[];
    renderItem: (item: T, index: number) => React.ReactNode;
    keyExtractor: (item: T) => string;
    empty?: React.ReactNode;
    className?: string;
}

export default function List<T>({
    items,
    renderItem,
    keyExtractor,
    empty = 'No items',
    className = '',
}: ListProps<T>): React.ReactElement {
    if (items.length === 0) {
        return <div>{empty}</div>;
    }

    return (
        <div className={className}>
            {items.map((item, idx) => (
                <div key={keyExtractor(item)}>
                    {renderItem(item, idx)}
                </div>
            ))}
        </div>
    );
}
```

---

## Tailwind CSS v4

### Import

Tailwind v4 uses CSS-first imports:

```typescript
// resources/js/app.tsx
import '@/css/app.css'; // Contains @import "tailwindcss"
```

### Class Usage

- Use Tailwind utilities directly in `className`
- Prefer utility composition over custom CSS
- Use `clsx` or similar for conditional classes

```typescript
import clsx from 'clsx';

interface BadgeProps {
    variant: 'success' | 'error' | 'warning';
    children: React.ReactNode;
}

export default function Badge({ variant, children }: BadgeProps) {
    return (
        <span
            className={clsx(
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                {
                    'bg-green-100 text-green-800': variant === 'success',
                    'bg-red-100 text-red-800': variant === 'error',
                    'bg-yellow-100 text-yellow-800': variant === 'warning',
                }
            )}
        >
            {children}
        </span>
    );
}
```

### Dark Mode

Tailwind v4 uses `dark:` prefix. If dark mode is implemented, all components must support it:

```typescript
<div className="bg-white dark:bg-gray-950 text-gray-900 dark:text-white">
    <h1 className="text-xl font-bold">Heading</h1>
</div>
```

### Spacing & Layout

- Use `gap-*` for spacing in flex/grid containers
- Use Tailwind's grid system for layouts
- Prefer `aspect-*` for fixed aspect ratios

```typescript
// ✅ CORRECT - gap for container spacing
<div className="flex flex-col gap-4">
    <Card />
    <Card />
    <Card />
</div>

// ✅ CORRECT - grid layout
<div className="grid grid-cols-3 gap-6">
    {items.map(item => <Card key={item.id} {...item} />)}
</div>

// ❌ WRONG - margins on child elements
<div className="flex flex-col">
    <Card className="mb-4" />
    <Card className="mb-4" />
</div>
```

---

## Inertia.js v2 Integration

### Pages

Pages live in `resources/js/pages/` and correspond to routes. Named by controller action:

```typescript
// resources/js/pages/Checklists/Index.tsx

import { PageProps } from '@inertiajs/react';
import ChecklistCard from '@/components/ChecklistCard';
import BaseLayout from '@/layouts/BaseLayout';

interface Checklist {
    id: string;
    name: string;
    location: string;
}

export default function ChecklistsIndex({
    checklists,
}: PageProps<{
    checklists: Checklist[];
}>) {
    return (
        <BaseLayout>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                {checklists.map(checklist => (
                    <ChecklistCard key={checklist.id} {...checklist} />
                ))}
            </div>
        </BaseLayout>
    );
}
```

### Link Navigation

Use Inertia's `<Link>` instead of `<a>`:

```typescript
import { Link } from '@inertiajs/react';

export default function Navigation() {
    return (
        <nav className="flex gap-4">
            <Link href="/checklists" className="text-blue-600 hover:text-blue-800">
                Checklists
            </Link>
            <Link href="/executions" className="text-blue-600 hover:text-blue-800">
                Executions
            </Link>
        </nav>
    );
}
```

### Forms with Inertia

Use the `<Form>` component for better DX:

```typescript
import { Form } from '@inertiajs/react';

export default function CreateChecklistForm() {
    return (
        <Form method="post" action="/api/v1/checklists">
            {({ errors, processing, recentlySuccessful }) => (
                <>
                    <div className="mb-4">
                        <label className="block text-sm font-medium mb-1">
                            Checklist Name
                        </label>
                        <input
                            type="text"
                            name="name"
                            className="w-full border rounded px-3 py-2"
                        />
                        {errors.name && (
                            <p className="text-red-600 text-sm mt-1">{errors.name}</p>
                        )}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
                    >
                        {processing ? 'Creating...' : 'Create Checklist'}
                    </button>

                    {recentlySuccessful && (
                        <p className="text-green-600 mt-2">Checklist created!</p>
                    )}
                </>
            )}
        </Form>
    );
}
```

---

## API Integration with Wayfinder

Wayfinder generates type-safe route functions from Laravel controllers.

### Basic Usage

```typescript
import { store } from '@/actions/App/Http/Controllers/ChecklistController';
import { Form } from '@inertiajs/react';

export default function CreateChecklistForm() {
    return (
        <Form {...store.form()}>
            <input type="text" name="name" />
            <button type="submit">Create</button>
        </Form>
    );
}
```

### Fetching Data

```typescript
import { index } from '@/actions/App/Http/Controllers/ChecklistController';

export default function ChecklistsList() {
    const [checklists, setChecklists] = React.useState<Checklist[]>([]);
    const [loading, setLoading] = React.useState(true);

    React.useEffect(() => {
        fetch(index.url())
            .then(res => res.json())
            .then(data => {
                setChecklists(data.data);
                setLoading(false);
            });
    }, []);

    if (loading) return <p>Loading...</p>;

    return (
        <div>
            {checklists.map(checklist => (
                <ChecklistCard key={checklist.id} {...checklist} />
            ))}
        </div>
    );
}
```

---

## Testing (Vitest + React Testing Library)

### Test File Location

```
resources/js/__tests__/
├── components/
│   ├── Button.test.tsx
│   ├── Card.test.tsx
│   └── [...]
├── hooks/
│   └── useForm.test.ts
└── pages/
    └── Checklists/
        └── Index.test.tsx
```

### Component Test Example

```typescript
// resources/js/__tests__/components/Button.test.tsx

import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Button from '@/components/Button';

describe('Button', () => {
    it('renders with label', () => {
        render(<Button>Click me</Button>);
        expect(screen.getByRole('button', { name: /click me/i })).toBeInTheDocument();
    });

    it('calls onClick when clicked', async () => {
        const user = userEvent.setup();
        const handleClick = vi.fn();

        render(<Button onClick={handleClick}>Click me</Button>);
        await user.click(screen.getByRole('button'));

        expect(handleClick).toHaveBeenCalledOnce();
    });

    it('disables button when disabled prop is true', () => {
        render(<Button disabled>Click me</Button>);
        expect(screen.getByRole('button')).toBeDisabled();
    });

    it('applies variant styles', () => {
        const { rerender } = render(<Button variant="primary">Click me</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-blue-600');

        rerender(<Button variant="secondary">Click me</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-gray-200');
    });
});
```

### Page/Integration Test Example

```typescript
// resources/js/__tests__/pages/Checklists/Index.test.tsx

import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { createMemoryHistory } from 'history';
import ChecklistsIndex from '@/pages/Checklists/Index';

describe('ChecklistsIndex Page', () => {
    const mockChecklists = [
        { id: '1', name: 'Daily Safety Check', location: 'Store A' },
        { id: '2', name: 'Closing Checklist', location: 'Store B' },
    ];

    it('renders checklist cards', () => {
        render(
            <ChecklistsIndex
                checklists={mockChecklists}
                // Inertia PageProps
            />
        );

        expect(screen.getByText('Daily Safety Check')).toBeInTheDocument();
        expect(screen.getByText('Closing Checklist')).toBeInTheDocument();
    });

    it('shows empty state when no checklists', () => {
        render(<ChecklistsIndex checklists={[]} />);
        expect(screen.getByText(/no checklists/i)).toBeInTheDocument();
    });
});
```

### Hook Test Example

```typescript
// resources/js/__tests__/hooks/useForm.test.ts

import { describe, it, expect } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import useForm from '@/hooks/useForm';

describe('useForm', () => {
    it('initializes with default values', () => {
        const { result } = renderHook(() =>
            useForm({
                email: '',
                password: '',
            })
        );

        expect(result.current.values).toEqual({
            email: '',
            password: '',
        });
    });

    it('updates field value', () => {
        const { result } = renderHook(() =>
            useForm({
                email: '',
            })
        );

        act(() => {
            result.current.setField('email', 'user@example.com');
        });

        expect(result.current.values.email).toBe('user@example.com');
    });
});
```

### Test Best Practices

- **Focus on behavior, not implementation** — test what the user sees, not how it works
- **Use semantic queries** — prefer `getByRole`, `getByLabelText` over `getByTestId`
- **Test accessibility** — if a component is inaccessible to tests, it's inaccessible to users
- **Avoid testing libraries/utilities** — test integration with your components
- **Mock external APIs** — use `vi.mock()` for API calls, don't hit real endpoints

---

## Composition & Reusability

### Extract Common Patterns

If you find yourself repeating JSX patterns, extract to a component:

```typescript
// ❌ WRONG — repeated pattern
<div className="p-6 bg-white rounded-lg shadow">
    <h2 className="text-xl font-bold mb-4">Title 1</h2>
    {/* content */}
</div>

<div className="p-6 bg-white rounded-lg shadow">
    <h2 className="text-xl font-bold mb-4">Title 2</h2>
    {/* content */}
</div>

// ✅ CORRECT — extracted component
<Card title="Title 1">{/* content */}</Card>
<Card title="Title 2">{/* content */}</Card>
```

### Conditional Rendering

Prefer early returns or ternary operators:

```typescript
// ✅ GOOD — early return
export default function UserProfile({ userId }: UserProfileProps) {
    if (!userId) {
        return <p>No user selected</p>;
    }

    return <div>{/* profile content */}</div>;
}

// ✅ GOOD — ternary for small branches
<div>
    {isLoading ? <Spinner /> : <UserCard user={user} />}
</div>

// ❌ AVOID — complex conditional logic in JSX
{isLoading && !error && user ? <UserCard user={user} /> : null}
```

---

## Code Style

### Imports Organization

```typescript
// 1. React/external libraries
import React from 'react';
import { useEffect } from 'react';
import { Form } from '@inertiajs/react';

// 2. Local imports (path aliases)
import Button from '@/components/Button';
import { formatDate } from '@/lib/dateUtils';
import { User } from '@/types/User';

// 3. Styles (if not global)
import styles from './Component.module.css';
```

### No Inline Functions in JSX Props

```typescript
// ❌ WRONG — recreates function on every render
<button onClick={() => handleClick(id)}>Delete</button>

// ✅ CORRECT — define handler outside JSX
const handleDelete = () => handleClick(id);
<button onClick={handleDelete}>Delete</button>

// ✅ ALSO OK — useCallback for performance-critical cases
const handleDelete = useCallback(() => {
    handleClick(id);
}, [id]);
```

### Comments

Keep code self-explanatory. Comments only for non-obvious logic:

```typescript
// ❌ WRONG — obvious comment
// Check if loading
if (isLoading) {
    return <Spinner />;
}

// ✅ CORRECT — comment explains why, not what
// Fetch data lazily to avoid initial page load delay
useEffect(() => {
    if (isVisible && !hasLoaded) {
        fetchData();
    }
}, [isVisible, hasLoaded]);
```

---

## Performance Considerations

### useMemo for Expensive Computations

```typescript
const expensiveList = useMemo(
    () => items.filter(filterCondition).map(transform),
    [items, filterCondition]
);
```

### useCallback for Stable Function References

```typescript
const handleEdit = useCallback((id: string) => {
    updateItem(id);
}, []);
```

### Lazy Loading Routes

```typescript
import { lazy } from 'react';

const ChecklistForm = lazy(() =>
    import('@/pages/Checklists/Form').then(m => ({
        default: m.default,
    }))
);
```

---

## Radix UI Components

Project includes Radix UI primitives (Dialog, Select, Checkbox, etc.). Check existing components before creating custom alternatives.

```typescript
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

export default function DeleteModal({ onConfirm, onCancel }: DeleteModalProps) {
    return (
        <Dialog open={true}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Confirm Delete</DialogTitle>
                </DialogHeader>
                <p>Are you sure?</p>
                <div className="flex gap-2 justify-end">
                    <Button variant="outline" onClick={onCancel}>
                        Cancel
                    </Button>
                    <Button variant="destructive" onClick={onConfirm}>
                        Delete
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
```

---

## Development Workflow

### Running Tests

```bash
cd src
npm run test              # Run all tests
npm run test -- Button    # Run tests matching "Button"
npm run test:watch       # Watch mode
```

### Type Checking

```bash
npm run test:types  # TypeScript check (no emit)
```

### Formatting & Linting

```bash
npm run lint        # Fix oxlint + prettier issues
npm run test:lint   # Check without fixing
```

### Building

```bash
npm run build       # Production build
npm run dev         # Development with Vite
```

---

## Common Patterns by Domain

### Forms (Inertia + Validation)

```typescript
import { Form } from '@inertiajs/react';

export default function ChecklistForm({ checklist }: ChecklistFormProps) {
    return (
        <Form method={checklist ? 'put' : 'post'} action="/api/v1/checklists">
            {({ errors, processing, wasSuccessful }) => (
                <>
                    <div className="mb-4">
                        <label htmlFor="name">Name</label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            defaultValue={checklist?.name}
                            className={errors.name ? 'border-red-600' : ''}
                        />
                        {errors.name && <p className="text-red-600">{errors.name}</p>}
                    </div>

                    <button type="submit" disabled={processing}>
                        {processing ? 'Saving...' : 'Save'}
                    </button>

                    {wasSuccessful && <p className="text-green-600">Saved!</p>}
                </>
            )}
        </Form>
    );
}
```

### Lists with Pagination (Wayfinder)

```typescript
import { index } from '@/actions/App/Http/Controllers/ChecklistController';

export default function ChecklistsList() {
    const [page, setPage] = React.useState(1);
    const [checklists, setChecklists] = React.useState<Checklist[]>([]);

    React.useEffect(() => {
        fetch(index.url() + `?page=${page}`)
            .then(res => res.json())
            .then(data => setChecklists(data.data));
    }, [page]);

    return (
        <div>
            <div className="grid grid-cols-1 gap-4">
                {checklists.map(c => (
                    <ChecklistCard key={c.id} checklist={c} />
                ))}
            </div>
            <div className="flex gap-2 mt-6">
                {[1, 2, 3].map(p => (
                    <button
                        key={p}
                        onClick={() => setPage(p)}
                        className={p === page ? 'font-bold' : ''}
                    >
                        {p}
                    </button>
                ))}
            </div>
        </div>
    );
}
```

### Role-Based UI

```typescript
import { usePage } from '@inertiajs/react';
import { PageProps } from '@inertiajs/react';

export default function Dashboard({
    auth,
}: PageProps<{
    auth: { user: { role: 'owner' | 'manager' | 'operator' } };
}>) {
    const { user } = usePage().props.auth;

    return (
        <div>
            {user.role === 'owner' && <OwnerDashboard />}
            {user.role === 'manager' && <ManagerDashboard />}
            {user.role === 'operator' && <OperatorDashboard />}
        </div>
    );
}
```

---

## Debugging Tips

- Use **React DevTools browser extension** — inspect component tree, props, hooks
- Use **VS Code Debugger** — set breakpoints in `.tsx` files during `npm run dev`
- Check **Network tab** — verify API requests and responses
- Use `console.log()` liberally in development — clean up before commit

---

## Security & Best Practices

- **Never hardcode API keys or secrets** — use environment variables
- **Validate user input** — even client-side validation improves UX
- **Sanitize data from APIs** — treat all external data as untrusted
- **Use HTTPS** — required for Inertia in production
- **Keep dependencies updated** — run `npm update` regularly
- **Use TypeScript strict mode** — prevents many bugs at compile time

