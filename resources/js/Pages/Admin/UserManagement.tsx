import AdminLayout from '@/Components/Layout/AdminLayout';
import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import type { AdminUserType, PaginatedAdminUsersType } from '@/Types/admin';

interface UserManagementProps {
    users: PaginatedAdminUsersType;
    searchQuery?: string;
}

export default function UserManagement({ users, searchQuery = '' }: UserManagementProps) {
    const [search, setSearch] = useState(searchQuery);

    function handleSearch(e: FormEvent) {
        e.preventDefault();
        if (search.trim() === '') {
            router.get('/admin/users');
            return;
        }
        router.get('/admin/users/search', { q: search.trim() });
    }

    function handlePageChange(page: number) {
        if (searchQuery) {
            router.get('/admin/users/search', { q: searchQuery, page: String(page) });
        } else {
            router.get('/admin/users', { page: String(page) });
        }
    }

    return (
        <AdminLayout>
            <div className="mx-auto max-w-6xl">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">User Management</h1>

                {/* Search bar */}
                <form onSubmit={handleSearch} className="mb-6">
                    <div className="flex gap-3">
                        <label htmlFor="user-search" className="sr-only">
                            Search users
                        </label>
                        <input
                            id="user-search"
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by name or email..."
                            className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <button
                            type="submit"
                            className="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Search
                        </button>
                    </div>
                </form>

                {searchQuery && (
                    <div className="mb-4 flex items-center gap-2">
                        <p className="text-sm text-gray-600">
                            Showing results for <span className="font-semibold">"{searchQuery}"</span>
                            {' '}({users.total} {users.total === 1 ? 'user' : 'users'} found)
                        </p>
                        <button
                            onClick={() => {
                                setSearch('');
                                router.get('/admin/users');
                            }}
                            className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Clear search
                        </button>
                    </div>
                )}

                {/* Users table */}
                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Name
                                </th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Email
                                </th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Role
                                </th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Subscription
                                </th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Registered
                                </th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Status
                                </th>
                                <th scope="col" className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white">
                            {users.users.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-6 py-12 text-center text-sm text-gray-500">
                                        {searchQuery ? 'No users match your search.' : 'No users found.'}
                                    </td>
                                </tr>
                            ) : (
                                users.users.map((user) => (
                                    <UserRow key={user.id} user={user} />
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {users.lastPage > 1 && (
                    <nav className="mt-6 flex items-center justify-between" aria-label="User list pagination">
                        <p className="text-sm text-gray-600">
                            Showing page {users.currentPage} of {users.lastPage} ({users.total} total users)
                        </p>
                        <div className="flex items-center gap-2">
                            <button
                                onClick={() => handlePageChange(users.currentPage - 1)}
                                disabled={users.currentPage <= 1}
                                className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Previous
                            </button>
                            {Array.from({ length: users.lastPage }, (_, i) => i + 1).map((page) => (
                                <button
                                    key={page}
                                    onClick={() => handlePageChange(page)}
                                    className={`rounded-lg px-3 py-2 text-sm font-medium ${
                                        page === users.currentPage
                                            ? 'bg-indigo-600 text-white'
                                            : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                                    }`}
                                    aria-current={page === users.currentPage ? 'page' : undefined}
                                >
                                    {page}
                                </button>
                            ))}
                            <button
                                onClick={() => handlePageChange(users.currentPage + 1)}
                                disabled={users.currentPage >= users.lastPage}
                                className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Next
                            </button>
                        </div>
                    </nav>
                )}
            </div>
        </AdminLayout>
    );
}

/* ------------------------------------------------------------------ */
/* User row with inline role assignment and suspend action             */
/* ------------------------------------------------------------------ */

function UserRow({ user }: { user: AdminUserType }) {
    const roleForm = useForm({ role: user.role as string });
    const suspendForm = useForm({});

    function handleRoleChange(newRole: string) {
        roleForm.setData('role', newRole);
        roleForm.post(`/admin/users/${user.id}/assign-role`, {
            preserveScroll: true,
        });
    }

    function handleSuspend() {
        if (!confirm(`Are you sure you want to suspend ${user.name}?`)) return;
        suspendForm.post(`/admin/users/${user.id}/suspend`, {
            preserveScroll: true,
        });
    }

    return (
        <tr>
            <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                {user.name}
            </td>
            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                {user.email}
            </td>
            <td className="whitespace-nowrap px-6 py-4">
                <label htmlFor={`role-${user.id}`} className="sr-only">
                    Role for {user.name}
                </label>
                <select
                    id={`role-${user.id}`}
                    value={user.role}
                    onChange={(e) => handleRoleChange(e.target.value)}
                    disabled={roleForm.processing}
                    className="rounded-lg border border-gray-300 px-2 py-1 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50"
                >
                    <option value="learner">Learner</option>
                    <option value="admin">Admin</option>
                </select>
            </td>
            <td className="whitespace-nowrap px-6 py-4">
                <SubscriptionBadge status={user.subscriptionStatus} />
            </td>
            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                <time dateTime={user.registrationDate}>
                    {new Date(user.registrationDate).toLocaleDateString()}
                </time>
            </td>
            <td className="whitespace-nowrap px-6 py-4">
                {user.isSuspended ? (
                    <span className="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">
                        Suspended
                    </span>
                ) : (
                    <span className="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                        Active
                    </span>
                )}
            </td>
            <td className="whitespace-nowrap px-6 py-4 text-right">
                {!user.isSuspended && (
                    <button
                        onClick={handleSuspend}
                        disabled={suspendForm.processing}
                        className="text-sm font-medium text-red-600 hover:text-red-500 disabled:opacity-50"
                    >
                        {suspendForm.processing ? 'Suspending…' : 'Suspend'}
                    </button>
                )}
            </td>
        </tr>
    );
}

/* ------------------------------------------------------------------ */
/* Subscription status badge                                           */
/* ------------------------------------------------------------------ */

function SubscriptionBadge({ status }: { status: string | null }) {
    if (!status) {
        return <span className="text-xs text-gray-400">None</span>;
    }

    const styles: Record<string, string> = {
        active: 'bg-green-100 text-green-700',
        grace_period: 'bg-yellow-100 text-yellow-700',
        suspended: 'bg-red-100 text-red-700',
        cancelled: 'bg-gray-100 text-gray-600',
    };

    const labels: Record<string, string> = {
        active: 'Active',
        grace_period: 'Grace Period',
        suspended: 'Suspended',
        cancelled: 'Cancelled',
    };

    return (
        <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${styles[status] ?? 'bg-gray-100 text-gray-600'}`}>
            {labels[status] ?? status}
        </span>
    );
}
