import { Link, usePage } from '@inertiajs/react';
import { ReactNode } from 'react';

interface Props {
    children: ReactNode;
    title?: string;
}

const navItems = [
    { label: 'Căutare Expeditii', href: '/expeditii/cautare', icon: 'pi pi-search' },
];

export default function OperatorLayout({ children, title }: Props) {
    const { url } = usePage();

    return (
        <div className="flex h-screen bg-gray-50">
            {/* Sidebar */}
            <aside className="w-56 bg-blue-800 text-white flex flex-col shrink-0">
                <div className="px-4 py-5 border-b border-blue-700">
                    <span className="text-lg font-bold tracking-wide">DSC Operator</span>
                </div>
                <nav className="flex-1 py-4">
                    {navItems.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`flex items-center gap-3 px-4 py-2.5 text-sm transition-colors ${
                                url.startsWith(item.href)
                                    ? 'bg-blue-600 text-white font-medium'
                                    : 'text-blue-100 hover:bg-blue-700'
                            }`}
                        >
                            <i className={item.icon} />
                            {item.label}
                        </Link>
                    ))}
                </nav>
                <div className="p-4 border-t border-blue-700">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="text-blue-200 text-sm hover:text-white"
                    >
                        <i className="pi pi-sign-out mr-2" />
                        Deconectare
                    </Link>
                </div>
            </aside>

            {/* Main content */}
            <div className="flex-1 flex flex-col overflow-hidden">
                {title && (
                    <header className="bg-white border-b px-6 py-3 shadow-sm shrink-0">
                        <h1 className="text-lg font-semibold text-gray-700">{title}</h1>
                    </header>
                )}
                <main className="flex-1 overflow-auto p-6">
                    {children}
                </main>
            </div>
        </div>
    );
}
