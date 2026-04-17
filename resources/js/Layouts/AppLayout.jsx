import { useState, useEffect, useRef } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    LayoutDashboard, ShoppingCart, UtensilsCrossed, BarChart3,
    Users, Package, Wallet, ClipboardList, ChefHat, TableProperties,
    Settings, LogOut, Bell, Menu, X, ChevronDown, Building2,
    UserCheck, Tag, TrendingUp, MapPin, Check,
} from 'lucide-react';
import { useDispatch, useSelector } from 'react-redux';
import { cn } from '@/lib/utils';
import NotificationToast from '@/Components/UI/NotificationToast';
import { setSelectedBranch, setBranchList } from '@/store/branchSlice';

const NAV_ITEMS = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, permission: null },
    { label: 'POS', href: '/pos', icon: ShoppingCart, permission: 'pos.view' },
    { label: 'Kitchen', href: '/kitchen', icon: ChefHat, permission: 'kitchen.view' },
    { label: 'Tables', href: '/tables', icon: TableProperties, permission: 'tables.view' },
    { label: 'Orders', href: '/orders', icon: ClipboardList, permission: 'orders.view' },
    { label: 'Menu', href: '/menu', icon: UtensilsCrossed, permission: 'menu.view' },
    { label: 'Inventory', href: '/inventory', icon: Package, permission: 'inventory.view' },
    { label: 'Customers', href: '/customers', icon: Users, permission: 'customers.view' },
    { label: 'Employees', href: '/employees', icon: UserCheck, permission: 'employees.view' },
    { label: 'Accounting', href: '/accounting', icon: Wallet, permission: 'accounting.view' },
    { label: 'Promotions', href: '/promotions', icon: Tag, permission: 'promotions.view' },
    { label: 'Reports', href: '/reports', icon: TrendingUp, permission: 'reports.view' },
    { label: 'Settings', href: '/settings', icon: Settings, permission: 'settings.view' },
];

function BranchSelector({ user }) {
    const dispatch = useDispatch();
    const { selected, list } = useSelector(s => s.branch);
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    const isAdmin = !user?.branch_id;

    useEffect(() => {
        if (!isAdmin) return;
        axios.get('/api/branches').then(res => {
            const branches = res.data;
            dispatch(setBranchList(branches));
            // Auto-select first branch if nothing stored
            if (!selected && branches.length > 0) {
                dispatch(setSelectedBranch(branches[0]));
            }
        }).catch(() => {});
    }, [isAdmin]);

    useEffect(() => {
        const handle = e => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', handle);
        return () => document.removeEventListener('mousedown', handle);
    }, []);

    if (!isAdmin) {
        return (
            <div className="flex items-center gap-2 text-sm text-gray-700 px-3 py-1.5 bg-purple-50 rounded-lg">
                <MapPin className="w-4 h-4 text-purple-600" />
                <span className="font-medium">{user?.branch?.name || 'Branch'}</span>
            </div>
        );
    }

    const displayName = selected?.name || 'Select Branch';

    return (
        <div className="relative" ref={ref}>
            <button
                onClick={() => setOpen(o => !o)}
                className="flex items-center gap-2 text-sm text-gray-700 px-3 py-1.5 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors border border-purple-200"
            >
                <Building2 className="w-4 h-4 text-purple-600" />
                <span className="font-medium">{displayName}</span>
                <ChevronDown className={cn('w-3.5 h-3.5 text-purple-500 transition-transform', open && 'rotate-180')} />
            </button>

            {open && (
                <div className="absolute right-0 top-full mt-1 w-56 bg-white rounded-xl shadow-lg border border-gray-200 z-50 py-1 overflow-hidden">
                    <div className="px-3 py-2 border-b border-gray-100">
                        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">Switch Branch</p>
                    </div>
                    {list.map(branch => (
                        <button
                            key={branch.id}
                            onClick={() => { dispatch(setSelectedBranch(branch)); setOpen(false); window.location.reload(); }}
                            className="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 hover:bg-purple-50 transition-colors text-left"
                        >
                            <div className={cn(
                                'w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0',
                                selected?.id === branch.id ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-600'
                            )}>
                                {branch.name[0]}
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="font-medium truncate">{branch.name}</p>
                                {branch.city && <p className="text-xs text-gray-400 truncate">{branch.city}</p>}
                            </div>
                            {selected?.id === branch.id && <Check className="w-4 h-4 text-purple-600 flex-shrink-0" />}
                        </button>
                    ))}
                    {list.length === 0 && (
                        <p className="px-3 py-3 text-sm text-gray-400 text-center">No branches found</p>
                    )}
                </div>
            )}
        </div>
    );
}

export default function AppLayout({ children, title }) {
    const { auth } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const [mobileOpen, setMobileOpen] = useState(false);
    const notifications = useSelector(s => s.notifications.items);

    const user = auth?.user;
    const currentPath = window.location.pathname;

    return (
        <div className="flex h-screen bg-gray-50 overflow-hidden">
            {/* Mobile overlay */}
            {mobileOpen && (
                <div
                    className="fixed inset-0 bg-black/50 z-20 lg:hidden"
                    onClick={() => setMobileOpen(false)}
                />
            )}

            {/* Sidebar */}
            <aside className={cn(
                'fixed inset-y-0 left-0 z-30 flex flex-col transition-all duration-300',
                'bg-gradient-to-b from-violet-900 via-purple-900 to-indigo-900',
                sidebarOpen ? 'w-60' : 'w-16',
                mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            )}>
                {/* Logo */}
                <div className="flex items-center h-16 px-4 border-b border-white/10">
                    <div className="flex items-center gap-3 overflow-hidden">
                        <div className="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                            <UtensilsCrossed className="w-5 h-5 text-white" />
                        </div>
                        {sidebarOpen && (
                            <div className="min-w-0">
                                <p className="text-white font-bold text-sm truncate">Spice Garden</p>
                                <p className="text-purple-300 text-xs truncate">{user?.branch?.name || 'All Branches'}</p>
                            </div>
                        )}
                    </div>
                    <button
                        onClick={() => setSidebarOpen(!sidebarOpen)}
                        className="ml-auto text-white/60 hover:text-white transition-colors hidden lg:flex"
                    >
                        <Menu className="w-4 h-4" />
                    </button>
                </div>

                {/* Nav */}
                <nav className="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto scrollbar-thin scrollbar-thumb-white/10">
                    {NAV_ITEMS.map(item => {
                        const active = currentPath.startsWith(item.href);
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all group',
                                    active
                                        ? 'bg-white/20 text-white font-medium'
                                        : 'text-white/60 hover:bg-white/10 hover:text-white',
                                )}
                                title={!sidebarOpen ? item.label : undefined}
                            >
                                <item.icon className={cn('w-5 h-5 flex-shrink-0', active ? 'text-white' : 'text-white/60 group-hover:text-white')} />
                                {sidebarOpen && <span className="truncate">{item.label}</span>}
                                {sidebarOpen && active && <div className="ml-auto w-1.5 h-1.5 rounded-full bg-white" />}
                            </Link>
                        );
                    })}
                </nav>

                {/* User */}
                <div className="border-t border-white/10 p-3">
                    <div className={cn('flex items-center gap-3', !sidebarOpen && 'justify-center')}>
                        <div className="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0 text-white text-sm font-bold">
                            {user?.name?.[0]?.toUpperCase() || 'U'}
                        </div>
                        {sidebarOpen && (
                            <div className="flex-1 min-w-0">
                                <p className="text-white text-sm font-medium truncate">{user?.name}</p>
                                <p className="text-purple-300 text-xs truncate">{user?.roles?.[0]?.name || 'Staff'}</p>
                            </div>
                        )}
                        {sidebarOpen && (
                            <Link href="/logout" method="post" as="button" className="text-white/60 hover:text-white transition-colors">
                                <LogOut className="w-4 h-4" />
                            </Link>
                        )}
                    </div>
                </div>
            </aside>

            {/* Main area */}
            <div className={cn(
                'flex-1 flex flex-col min-h-0 transition-all duration-300',
                sidebarOpen ? 'lg:ml-60' : 'lg:ml-16',
            )}>
                {/* Top bar */}
                <header className="h-16 bg-white border-b border-gray-200 flex items-center px-4 gap-4 flex-shrink-0">
                    <button
                        onClick={() => setMobileOpen(true)}
                        className="lg:hidden text-gray-500 hover:text-gray-700"
                    >
                        <Menu className="w-5 h-5" />
                    </button>

                    <h1 className="text-lg font-semibold text-gray-900">{title}</h1>

                    <div className="ml-auto flex items-center gap-3">
                        <button className="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                            <Bell className="w-5 h-5" />
                            {notifications.length > 0 && (
                                <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full" />
                            )}
                        </button>

                        <BranchSelector user={user} />
                    </div>
                </header>

                {/* Page content */}
                <main className="flex-1 overflow-y-auto p-6">
                    {children}
                </main>
            </div>

            {/* Toast notifications */}
            <NotificationToast />
        </div>
    );
}
