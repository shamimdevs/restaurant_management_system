import { useState, useCallback } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import axios from 'axios';
import {
    BarChart3, TrendingUp, Download, Filter,
    DollarSign, ShoppingCart, Package, FileText,
    PieChart, Calendar, RefreshCw, AlertCircle,
} from 'lucide-react';
import {
    BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
    LineChart, Line, Legend, PieChart as RPieChart, Pie, Cell, AreaChart, Area,
} from 'recharts';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import { cn, formatCurrency, formatDate } from '@/lib/utils';

const PIE_COLORS = ['#7c3aed', '#4f46e5', '#0891b2', '#059669', '#d97706', '#dc2626', '#db2777'];

// ── Date preset buttons ────────────────────────────────────────────────────
const PRESETS = [
    { label: 'Today',      days: 0 },
    { label: 'Yesterday',  days: 1 },
    { label: 'Last 7d',    days: 7 },
    { label: 'Last 30d',   days: 30 },
    { label: 'This Month', type: 'month' },
];

function getPresetDates(preset) {
    const now   = new Date();
    const today = now.toISOString().split('T')[0];
    if (preset.type === 'month') {
        return { from: today.slice(0,8) + '01', to: today };
    }
    const from = new Date(now);
    from.setDate(from.getDate() - preset.days);
    return { from: from.toISOString().split('T')[0], to: today };
}

// ── KPI Summary ────────────────────────────────────────────────────────────
function KpiRow({ data }) {
    const kpis = [
        { label: 'Total Revenue',  value: formatCurrency(data?.total_revenue ?? 0), icon: DollarSign, color: 'text-violet-600' },
        { label: 'Total Orders',   value: data?.total_orders ?? 0,                  icon: ShoppingCart, color: 'text-blue-600' },
        { label: 'VAT Collected',  value: formatCurrency(data?.total_vat ?? 0),     icon: FileText, color: 'text-amber-600' },
        { label: 'Avg Order',      value: data?.total_orders > 0
            ? formatCurrency(data.total_revenue / data.total_orders) : '৳0',         icon: BarChart3, color: 'text-emerald-600' },
    ];
    return (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            {kpis.map(k => (
                <div key={k.label} className="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
                    <div className="flex items-center gap-2 mb-2">
                        <k.icon className={cn('w-5 h-5', k.color)} />
                        <p className="text-xs text-gray-500">{k.label}</p>
                    </div>
                    <p className="text-2xl font-bold text-gray-900">{k.value}</p>
                </div>
            ))}
        </div>
    );
}

// ── Main Page ──────────────────────────────────────────────────────────────
export default function ReportsDashboard({ daily_sales, top_items }) {
    const today = new Date().toISOString().split('T')[0];
    const [activeTab, setActiveTab]   = useState('sales');
    const [from, setFrom]             = useState(today.slice(0,8) + '01');
    const [to, setTo]                 = useState(today);
    const [loading, setLoading]       = useState(false);
    const [salesData, setSalesData]   = useState(null);
    const [topData, setTopData]       = useState(top_items ?? []);
    const [vatData, setVatData]       = useState(null);
    const [expData, setExpData]       = useState(null);
    const [plData, setPlData]         = useState(null);
    const [branchData, setBranchData] = useState(null);

    const applyPreset = (preset) => {
        const d = getPresetDates(preset);
        setFrom(d.from);
        setTo(d.to);
    };

    const fetchData = useCallback(async () => {
        setLoading(true);
        try {
            const params = { from, to };
            if (activeTab === 'sales') {
                const [sales, items] = await Promise.all([
                    axios.get('/api/reports/sales',     { params }),
                    axios.get('/api/reports/top-items', { params: { ...params, limit: 10 } }),
                ]);
                setSalesData(sales.data);
                setTopData(items.data);
            } else if (activeTab === 'vat') {
                const res = await axios.get('/api/reports/vat', { params });
                setVatData(res.data);
            } else if (activeTab === 'expenses') {
                const res = await axios.get('/api/reports/expenses', { params });
                setExpData(res.data);
            } else if (activeTab === 'pl') {
                const res = await axios.get('/api/reports/profit-loss', { params });
                setPlData(res.data);
            } else if (activeTab === 'branches') {
                const res = await axios.get('/api/reports/branch-performance', { params });
                setBranchData(res.data);
            }
        } finally { setLoading(false); }
    }, [activeTab, from, to]);

    const exportCsv = async () => {
        // Simple CSV export of current view data
        const data = activeTab === 'sales' ? salesData?.daily :
                     activeTab === 'vat'   ? vatData?.rows : [];
        if (!data?.length) return alert('No data to export');

        const headers = Object.keys(data[0]).join(',');
        const rows    = data.map(r => Object.values(r).join(',')).join('\n');
        const blob    = new Blob([headers + '\n' + rows], { type: 'text/csv' });
        const link    = document.createElement('a');
        link.href     = URL.createObjectURL(blob);
        link.download = `report-${activeTab}-${from}-${to}.csv`;
        link.click();
    };

    // daily sales chart data
    const dailyChart = salesData?.daily ?? daily_sales?.daily ?? [];

    // payment method pie
    const paymentPie = salesData
        ? Object.entries(salesData.by_payment ?? {}).map(([k, v]) => ({ name: k, value: parseFloat(v) }))
        : Object.entries(daily_sales?.by_payment ?? {}).map(([k, v]) => ({ name: k, value: parseFloat(v) }));

    return (
        <AppLayout title="Reports & Analytics">
            {/* Top toolbar */}
            <div className="flex flex-wrap items-center gap-3 mb-5">
                {/* Presets */}
                <div className="flex gap-2 flex-wrap">
                    {PRESETS.map(p => (
                        <button key={p.label} onClick={() => applyPreset(p)}
                            className="px-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-medium text-gray-600 hover:border-violet-300 hover:text-violet-700 transition-colors">
                            {p.label}
                        </button>
                    ))}
                </div>
                <div className="flex items-center gap-2 ml-auto">
                    <Input type="date" value={from} onChange={e => setFrom(e.target.value)} className="text-sm py-1.5" />
                    <span className="text-gray-400 text-sm">–</span>
                    <Input type="date" value={to}   onChange={e => setTo(e.target.value)} className="text-sm py-1.5" />
                    <Button size="sm" onClick={fetchData} loading={loading}>
                        <Filter className="w-4 h-4" /> Generate
                    </Button>
                    <Button size="sm" variant="outline" onClick={exportCsv}>
                        <Download className="w-4 h-4" /> CSV
                    </Button>
                </div>
            </div>

            {/* Tab Nav */}
            <div className="flex gap-1 mb-5 bg-white border border-gray-200 rounded-2xl p-1 w-fit shadow-sm">
                {[
                    { key: 'sales',    label: 'Sales',       icon: TrendingUp },
                    { key: 'vat',      label: 'VAT',         icon: FileText },
                    { key: 'expenses', label: 'Expenses',    icon: DollarSign },
                    { key: 'pl',       label: 'P&L',         icon: BarChart3 },
                    { key: 'branches', label: 'Branches',    icon: Package },
                ].map(t => (
                    <button key={t.key} onClick={() => setActiveTab(t.key)}
                        className={cn(
                            'flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-medium transition-all',
                            activeTab === t.key
                                ? 'bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-sm'
                                : 'text-gray-600 hover:text-gray-800'
                        )}>
                        <t.icon className="w-4 h-4" />
                        {t.label}
                    </button>
                ))}
            </div>

            {/* KPI row from latest fetch or initial data */}
            <KpiRow data={salesData ?? daily_sales} />

            {/* ── Sales Tab ── */}
            {activeTab === 'sales' && (
                <div className="grid grid-cols-1 xl:grid-cols-3 gap-5">
                    {/* Daily Sales Bar */}
                    <div className="xl:col-span-2 bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                        <h3 className="font-semibold text-gray-800 mb-4">Daily Sales</h3>
                        {dailyChart.length > 0 ? (
                            <ResponsiveContainer width="100%" height={250}>
                                <BarChart data={dailyChart}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" />
                                    <XAxis dataKey="date" tick={{ fontSize: 10 }}
                                        tickFormatter={d => d.slice(5)} />
                                    <YAxis tick={{ fontSize: 10 }} tickFormatter={v => '৳' + (v/1000).toFixed(0) + 'k'} />
                                    <Tooltip formatter={v => [formatCurrency(v), 'Revenue']} />
                                    <Bar dataKey="revenue" fill="#7c3aed" radius={[4,4,0,0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        ) : (
                            <div className="h-[250px] flex items-center justify-center text-gray-300">
                                <BarChart3 className="w-16 h-16" />
                            </div>
                        )}
                    </div>

                    {/* Payment Method Pie */}
                    <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                        <h3 className="font-semibold text-gray-800 mb-4">Payment Methods</h3>
                        {paymentPie.length > 0 ? (
                            <>
                                <ResponsiveContainer width="100%" height={180}>
                                    <RPieChart>
                                        <Pie data={paymentPie} cx="50%" cy="50%" innerRadius={50} outerRadius={75}
                                            dataKey="value" paddingAngle={3}>
                                            {paymentPie.map((_, i) => (
                                                <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip formatter={v => formatCurrency(v)} />
                                    </RPieChart>
                                </ResponsiveContainer>
                                <div className="space-y-1.5 mt-2">
                                    {paymentPie.map((d, i) => (
                                        <div key={d.name} className="flex items-center justify-between text-sm">
                                            <div className="flex items-center gap-2">
                                                <div className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: PIE_COLORS[i % PIE_COLORS.length] }} />
                                                <span className="text-gray-600 capitalize">{d.name}</span>
                                            </div>
                                            <span className="font-medium text-gray-800">{formatCurrency(d.value)}</span>
                                        </div>
                                    ))}
                                </div>
                            </>
                        ) : (
                            <div className="h-[180px] flex items-center justify-center text-gray-200">
                                <PieChart className="w-12 h-12" />
                            </div>
                        )}
                    </div>

                    {/* Top selling items */}
                    <div className="xl:col-span-3 bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                        <h3 className="font-semibold text-gray-800 mb-4">Top Selling Items</h3>
                        {topData.length === 0 ? (
                            <p className="text-gray-400 text-sm text-center py-8">No data available</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-left text-xs text-gray-400 border-b border-gray-100">
                                            <th className="pb-2 font-medium">#</th>
                                            <th className="pb-2 font-medium">Item</th>
                                            <th className="pb-2 font-medium">Qty Sold</th>
                                            <th className="pb-2 font-medium">Revenue</th>
                                            <th className="pb-2 font-medium">Avg Price</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-50">
                                        {topData.map((item, i) => (
                                            <tr key={i} className="hover:bg-gray-50/50">
                                                <td className="py-2.5 pr-4 text-gray-400 font-mono text-xs">{i + 1}</td>
                                                <td className="py-2.5 pr-4">
                                                    <p className="font-medium text-gray-800">{item.name}</p>
                                                </td>
                                                <td className="py-2.5 pr-4 font-semibold text-gray-700">{item.quantity}</td>
                                                <td className="py-2.5 pr-4 font-semibold text-violet-700">{formatCurrency(item.revenue)}</td>
                                                <td className="py-2.5 text-gray-500">
                                                    {item.quantity > 0 ? formatCurrency(item.revenue / item.quantity) : '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* ── VAT Tab ── */}
            {activeTab === 'vat' && (
                <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="font-semibold text-gray-800">VAT Report</h3>
                        <span className="text-xs text-gray-500">Bangladesh NBR Compliant</span>
                    </div>
                    {!vatData ? (
                        <div className="py-12 text-center">
                            <FileText className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                            <p className="text-gray-500">Select date range and click Generate</p>
                        </div>
                    ) : (
                        <div>
                            <div className="grid grid-cols-3 gap-4 mb-5">
                                {[
                                    { label: 'Taxable Sales',    value: formatCurrency(vatData.taxable_amount ?? 0) },
                                    { label: 'VAT Collected',    value: formatCurrency(vatData.vat_amount ?? 0) },
                                    { label: 'Net Sales (incl. VAT)', value: formatCurrency((vatData.taxable_amount ?? 0) + (vatData.vat_amount ?? 0)) },
                                ].map(s => (
                                    <div key={s.label} className="bg-gray-50 rounded-xl p-4">
                                        <p className="text-xl font-bold text-gray-900">{s.value}</p>
                                        <p className="text-xs text-gray-500 mt-0.5">{s.label}</p>
                                    </div>
                                ))}
                            </div>
                            {vatData.rows?.length > 0 && (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="text-left text-xs text-gray-400 border-b border-gray-100">
                                                <th className="pb-2">VAT Rate</th>
                                                <th className="pb-2">Orders</th>
                                                <th className="pb-2">Taxable Amount</th>
                                                <th className="pb-2">VAT Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-50">
                                            {vatData.rows.map((r, i) => (
                                                <tr key={i}>
                                                    <td className="py-2.5 font-medium">{r.rate}%</td>
                                                    <td className="py-2.5 text-gray-600">{r.order_count}</td>
                                                    <td className="py-2.5">{formatCurrency(r.taxable_amount)}</td>
                                                    <td className="py-2.5 font-semibold text-amber-700">{formatCurrency(r.vat_amount)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            )}

            {/* ── Expenses Tab ── */}
            {activeTab === 'expenses' && (
                <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <h3 className="font-semibold text-gray-800 mb-4">Expense Report</h3>
                    {!expData ? (
                        <div className="py-12 text-center">
                            <DollarSign className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                            <p className="text-gray-500">Select date range and click Generate</p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            <div className="bg-red-50 rounded-xl p-4 inline-block">
                                <p className="text-2xl font-bold text-red-700">{formatCurrency(expData.total ?? 0)}</p>
                                <p className="text-sm text-red-500">Total Expenses</p>
                            </div>
                            {expData.by_category?.length > 0 && (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <ResponsiveContainer width="100%" height={220}>
                                        <BarChart data={expData.by_category} layout="vertical">
                                            <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" />
                                            <XAxis type="number" tick={{ fontSize: 10 }} tickFormatter={v => '৳' + (v/1000).toFixed(0) + 'k'} />
                                            <YAxis type="category" dataKey="category" tick={{ fontSize: 10 }} width={100} />
                                            <Tooltip formatter={v => formatCurrency(v)} />
                                            <Bar dataKey="total" fill="#ef4444" radius={[0,4,4,0]} />
                                        </BarChart>
                                    </ResponsiveContainer>
                                    <div className="space-y-2">
                                        {expData.by_category.map((c, i) => (
                                            <div key={i} className="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg text-sm">
                                                <span className="text-gray-700">{c.category}</span>
                                                <span className="font-semibold text-red-600">{formatCurrency(c.total)}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            )}

            {/* ── P&L Tab ── */}
            {activeTab === 'pl' && (
                <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <h3 className="font-semibold text-gray-800 mb-4">Profit & Loss Statement</h3>
                    {!plData ? (
                        <div className="py-12 text-center">
                            <BarChart3 className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                            <p className="text-gray-500">Select date range and click Generate</p>
                        </div>
                    ) : (
                        <div className="max-w-lg">
                            <table className="w-full text-sm">
                                <tbody className="divide-y divide-gray-100">
                                    <tr className="bg-emerald-50">
                                        <td colSpan={2} className="py-2 px-3 font-semibold text-emerald-800 text-xs uppercase">Revenue</td>
                                    </tr>
                                    <tr>
                                        <td className="py-2.5 pl-4 text-gray-700">Total Sales</td>
                                        <td className="py-2.5 text-right font-medium text-emerald-700">{formatCurrency(plData.income ?? 0)}</td>
                                    </tr>
                                    <tr className="bg-red-50">
                                        <td colSpan={2} className="py-2 px-3 font-semibold text-red-800 text-xs uppercase">Expenses</td>
                                    </tr>
                                    <tr>
                                        <td className="py-2.5 pl-4 text-gray-700">Total Expenses</td>
                                        <td className="py-2.5 text-right text-red-600">({formatCurrency(plData.expenses ?? 0)})</td>
                                    </tr>
                                    <tr className="font-bold bg-gray-50 border-t-2 border-gray-200">
                                        <td className="py-3 px-2 text-gray-900">Net Profit / (Loss)</td>
                                        <td className={cn('py-3 text-right text-lg',
                                            (plData.net_profit ?? 0) >= 0 ? 'text-emerald-700' : 'text-red-700')}>
                                            {formatCurrency(plData.net_profit ?? 0)}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}

            {/* ── Branch Performance Tab ── */}
            {activeTab === 'branches' && (
                <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <h3 className="font-semibold text-gray-800 mb-4">Branch Performance</h3>
                    {!branchData ? (
                        <div className="py-12 text-center">
                            <Package className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                            <p className="text-gray-500">Select date range and click Generate</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-left text-xs text-gray-400 border-b border-gray-100">
                                        <th className="pb-2">Branch</th>
                                        <th className="pb-2">Orders</th>
                                        <th className="pb-2">Revenue</th>
                                        <th className="pb-2">Avg Order</th>
                                        <th className="pb-2">VAT</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {branchData.branches?.map((b, i) => (
                                        <tr key={i} className="hover:bg-gray-50/50">
                                            <td className="py-3 font-medium text-gray-800">{b.name}</td>
                                            <td className="py-3 text-gray-600">{b.orders}</td>
                                            <td className="py-3 font-semibold text-violet-700">{formatCurrency(b.revenue)}</td>
                                            <td className="py-3 text-gray-600">{formatCurrency(b.avg_order)}</td>
                                            <td className="py-3 text-amber-700">{formatCurrency(b.vat)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}
        </AppLayout>
    );
}
