import AppLayout from '@/Layouts/AppLayout';
import { StatCard } from '@/Components/UI/Card';
import { formatCurrency, formatTime } from '@/lib/utils';
import {
    TrendingUp, ShoppingCart, Users, Package,
    ArrowRight, Clock, CheckCircle, AlertTriangle,
} from 'lucide-react';
import {
    AreaChart, Area, XAxis, YAxis, CartesianGrid,
    Tooltip, ResponsiveContainer, PieChart, Pie, Cell,
} from 'recharts';
import { Link } from '@inertiajs/react';
import Badge from '@/Components/UI/Badge';

const COLORS = ['#7c3aed', '#4f46e5', '#0891b2', '#059669', '#d97706'];

export default function Dashboard({ stats, hourlyRevenue, topItems, recentOrders, alerts }) {
    return (
        <AppLayout title="Dashboard">
            {/* KPI Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                <StatCard
                    title="Today's Revenue"
                    value={formatCurrency(stats?.today_revenue ?? 0)}
                    icon={TrendingUp}
                    change={stats?.revenue_change}
                    changeLabel="vs yesterday"
                    color="violet"
                />
                <StatCard
                    title="Orders Today"
                    value={stats?.today_orders ?? 0}
                    icon={ShoppingCart}
                    change={stats?.orders_change}
                    changeLabel="vs yesterday"
                    color="blue"
                />
                <StatCard
                    title="Customers Today"
                    value={stats?.today_customers ?? 0}
                    icon={Users}
                    color="green"
                />
                <StatCard
                    title="Avg Order Value"
                    value={formatCurrency(stats?.avg_order_value ?? 0)}
                    icon={Package}
                    color="orange"
                />
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
                {/* Revenue Chart */}
                <div className="xl:col-span-2 bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="font-semibold text-gray-900">Hourly Revenue</h3>
                        <span className="text-xs text-gray-500">{new Date().toLocaleDateString('en-BD', { weekday: 'long', day: 'numeric', month: 'long' })}</span>
                    </div>
                    <ResponsiveContainer width="100%" height={220}>
                        <AreaChart data={hourlyRevenue || []}>
                            <defs>
                                <linearGradient id="revGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor="#7c3aed" stopOpacity={0.3} />
                                    <stop offset="95%" stopColor="#7c3aed" stopOpacity={0} />
                                </linearGradient>
                            </defs>
                            <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" />
                            <XAxis dataKey="hour" tick={{ fontSize: 11, fill: '#9ca3af' }} />
                            <YAxis tick={{ fontSize: 11, fill: '#9ca3af' }} tickFormatter={v => '৳' + v.toLocaleString()} />
                            <Tooltip
                                formatter={(v) => [formatCurrency(v), 'Revenue']}
                                contentStyle={{ borderRadius: '12px', border: '1px solid #e5e7eb', boxShadow: '0 4px 24px rgba(0,0,0,0.08)' }}
                            />
                            <Area type="monotone" dataKey="revenue" stroke="#7c3aed" strokeWidth={2} fill="url(#revGradient)" />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>

                {/* Top Items */}
                <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="font-semibold text-gray-900">Top Items</h3>
                    </div>
                    {topItems?.length ? (
                        <div className="flex flex-col gap-3">
                            {topItems.slice(0, 5).map((item, i) => (
                                <div key={i} className="flex items-center gap-3">
                                    <div className="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                        style={{ backgroundColor: COLORS[i % COLORS.length] }}>
                                        {i + 1}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-800 truncate">{item.name}</p>
                                        <p className="text-xs text-gray-500">{item.quantity} sold</p>
                                    </div>
                                    <p className="text-sm font-semibold text-gray-900 flex-shrink-0">{formatCurrency(item.revenue)}</p>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-gray-400 text-sm text-center py-8">No data for today</p>
                    )}
                </div>
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                {/* Recent Orders */}
                <div className="bg-white rounded-2xl border border-gray-200 shadow-sm">
                    <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 className="font-semibold text-gray-900">Recent Orders</h3>
                        <Link href="/pos" className="text-sm text-violet-600 hover:underline flex items-center gap-1">
                            POS <ArrowRight className="w-3 h-3" />
                        </Link>
                    </div>
                    <div className="divide-y divide-gray-100">
                        {(recentOrders || []).slice(0, 8).map(order => (
                            <div key={order.id} className="flex items-center gap-3 px-5 py-3">
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-medium text-gray-800">{order.order_number}</p>
                                    <p className="text-xs text-gray-500 flex items-center gap-1">
                                        <Clock className="w-3 h-3" />
                                        {formatTime(order.created_at)}
                                        {order.table && ` · Table ${order.table.table_number}`}
                                    </p>
                                </div>
                                <Badge status={order.status} />
                                <p className="text-sm font-semibold text-gray-900">{formatCurrency(order.total)}</p>
                            </div>
                        ))}
                        {(!recentOrders || recentOrders.length === 0) && (
                            <div className="py-12 text-center text-gray-400 text-sm">No orders today</div>
                        )}
                    </div>
                </div>

                {/* Alerts & Activity */}
                <div className="bg-white rounded-2xl border border-gray-200 shadow-sm">
                    <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 className="font-semibold text-gray-900">Alerts</h3>
                        <span className="bg-red-100 text-red-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                            {alerts?.length || 0}
                        </span>
                    </div>
                    <div className="divide-y divide-gray-100">
                        {(alerts || []).slice(0, 8).map(alert => (
                            <div key={alert.id} className="flex items-start gap-3 px-5 py-3">
                                <AlertTriangle className="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" />
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-medium text-gray-800">{alert.ingredient?.name || 'Unknown'}</p>
                                    <p className="text-xs text-gray-500">
                                        Stock: {alert.current_level} {alert.unit} · Min: {alert.min_level} {alert.unit}
                                    </p>
                                </div>
                                <Badge status="warning" className="flex-shrink-0">Low</Badge>
                            </div>
                        ))}
                        {(!alerts || alerts.length === 0) && (
                            <div className="py-12 text-center text-gray-400 text-sm">
                                <CheckCircle className="w-8 h-8 text-green-400 mx-auto mb-2" />
                                All clear — no alerts
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
