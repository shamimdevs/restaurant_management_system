import { useState, useEffect, useCallback } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import axios from 'axios';
import {
    Wallet, TrendingUp, TrendingDown, DollarSign,
    Plus, Search, Filter, Save, FileText,
    PieChart, BarChart3, ArrowUpRight, ArrowDownRight,
    BookOpen, Receipt, CreditCard,
} from 'lucide-react';
import {
    PieChart as RPieChart, Pie, Cell, Tooltip, ResponsiveContainer,
    AreaChart, Area, XAxis, YAxis, CartesianGrid, Legend,
} from 'recharts';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import { cn, formatCurrency, formatDate } from '@/lib/utils';

const PIE_COLORS = ['#7c3aed', '#4f46e5', '#0891b2', '#059669', '#d97706', '#dc2626'];

// ── Add Expense Modal ──────────────────────────────────────────────────────
function ExpenseModal({ onClose, onSaved }) {
    const [categories, setCategories] = useState([]);
    const [form, setForm] = useState({
        title:               '',
        amount:              '',
        expense_date:        new Date().toISOString().split('T')[0],
        expense_category_id: '',
        payment_method:      'cash',
        notes:               '',
    });
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm(p => ({ ...p, [k]: v }));

    useEffect(() => {
        axios.get('/api/accounting/expense-categories').then(r => setCategories(r.data));
    }, []);

    const submit = async () => {
        setSaving(true); setErrors({});
        try {
            await axios.post('/api/accounting/expenses', form);
            onSaved();
        } catch (e) { setErrors(e.response?.data?.errors || {}); }
        finally { setSaving(false); }
    };

    return (
        <Modal open onClose={onClose} title="Record Expense">
            <div className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <Input value={form.title} onChange={e => set('title', e.target.value)}
                        error={errors.title?.[0]} placeholder="e.g. Gas bill payment" />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Amount (৳) *</label>
                        <Input type="number" min={0} step="0.01" value={form.amount}
                            onChange={e => set('amount', e.target.value)} error={errors.amount?.[0]} placeholder="0.00" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                        <Input type="date" value={form.expense_date} onChange={e => set('expense_date', e.target.value)} />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select value={form.expense_category_id} onChange={e => set('expense_category_id', e.target.value)}
                            className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="">Uncategorized</option>
                            {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select value={form.payment_method} onChange={e => set('payment_method', e.target.value)}
                            className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="bank">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea value={form.notes} onChange={e => set('notes', e.target.value)} rows={2}
                        placeholder="Optional notes..."
                        className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 resize-none" />
                </div>
                <div className="flex gap-3 pt-2">
                    <Button variant="outline" className="flex-1" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>
                        <Save className="w-4 h-4" /> Record Expense
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Summary KPI Card ───────────────────────────────────────────────────────
function KpiCard({ icon: Icon, label, value, trend, color, bg }) {
    return (
        <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
            <div className="flex items-center justify-between mb-3">
                <div className={cn('w-10 h-10 rounded-xl flex items-center justify-center', bg)}>
                    <Icon className={cn('w-5 h-5', color)} />
                </div>
                {trend !== undefined && (
                    <span className={cn('text-xs font-medium flex items-center gap-0.5',
                        trend >= 0 ? 'text-emerald-600' : 'text-red-600')}>
                        {trend >= 0 ? <ArrowUpRight className="w-3.5 h-3.5" /> : <ArrowDownRight className="w-3.5 h-3.5" />}
                        {Math.abs(trend)}%
                    </span>
                )}
            </div>
            <p className="text-2xl font-bold text-gray-900">{value}</p>
            <p className="text-sm text-gray-500 mt-0.5">{label}</p>
        </div>
    );
}

// ── Main Page ──────────────────────────────────────────────────────────────
export default function AccountingIndex({ summary = {} }) {
    const [tab, setTab]             = useState('overview');
    const [expenses, setExpenses]   = useState([]);
    const [journals, setJournals]   = useState([]);
    const [accounts, setAccounts]   = useState([]);
    const [plResult, setPlResult]   = useState(null);
    const [loading, setLoading]     = useState(false);
    const [expenseModal, setExpenseModal] = useState(false);
    const [dateFrom, setDateFrom]   = useState(new Date().toISOString().slice(0,8) + '01');
    const [dateTo, setDateTo]       = useState(new Date().toISOString().split('T')[0]);
    const [expMeta, setExpMeta]     = useState({});

    const fetchExpenses = useCallback(async () => {
        setLoading(true);
        try {
            const res = await axios.get('/api/accounting/expenses', { params: { from: dateFrom, to: dateTo } });
            setExpenses(res.data.data || []);
            setExpMeta(res.data.meta || {});
        } finally { setLoading(false); }
    }, [dateFrom, dateTo]);

    const fetchJournals = useCallback(async () => {
        if (tab !== 'journal') return;
        const res = await axios.get('/api/accounting/journals', { params: { from: dateFrom, to: dateTo } });
        setJournals(res.data.data || []);
    }, [tab, dateFrom, dateTo]);

    const fetchAccounts = useCallback(async () => {
        if (tab !== 'accounts') return;
        const res = await axios.get('/api/accounting/accounts');
        setAccounts(res.data || []);
    }, [tab]);

    useEffect(() => { if (tab === 'expenses') fetchExpenses(); }, [tab, fetchExpenses]);
    useEffect(() => { fetchJournals(); }, [fetchJournals]);
    useEffect(() => { fetchAccounts(); }, [fetchAccounts]);

    const pieData = (summary.expense_by_cat || []).map((c, i) => ({
        name: c.category,
        value: parseFloat(c.total),
    }));

    // Monthly income vs expense for area chart (dummy fill if API not available)
    const chartData = Array.from({ length: 6 }, (_, i) => {
        const d = new Date();
        d.setMonth(d.getMonth() - (5 - i));
        return {
            month: d.toLocaleString('en-BD', { month: 'short' }),
            income: 0,
            expense: 0,
        };
    });

    return (
        <AppLayout title="Accounting">
            {/* ── KPI summary ── */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <KpiCard
                    icon={TrendingUp} label="Month Revenue"
                    value={formatCurrency(summary.total_income ?? 0)}
                    bg="bg-emerald-50" color="text-emerald-600"
                />
                <KpiCard
                    icon={TrendingDown} label="Month Expenses"
                    value={formatCurrency(summary.total_expense ?? 0)}
                    bg="bg-red-50" color="text-red-600"
                />
                <KpiCard
                    icon={DollarSign} label="Net Profit"
                    value={formatCurrency(summary.net_profit ?? 0)}
                    bg={(summary.net_profit ?? 0) >= 0 ? 'bg-violet-50' : 'bg-red-50'}
                    color={(summary.net_profit ?? 0) >= 0 ? 'text-violet-600' : 'text-red-600'}
                />
            </div>

            {/* ── Main card with tabs ── */}
            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm">
                {/* Tabs */}
                <div className="flex items-center justify-between px-4 pt-4 border-b border-gray-100">
                    <div className="flex gap-1">
                        {[
                            { key: 'overview',  label: 'Overview' },
                            { key: 'expenses',  label: 'Expenses' },
                            { key: 'journal',   label: 'Journal' },
                            { key: 'accounts',  label: 'Chart of Accounts' },
                            { key: 'pl',        label: 'P&L' },
                        ].map(t => (
                            <button key={t.key} onClick={() => setTab(t.key)}
                                className={cn(
                                    'px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap',
                                    tab === t.key ? 'border-violet-600 text-violet-600' : 'border-transparent text-gray-500 hover:text-gray-700'
                                )}>
                                {t.label}
                            </button>
                        ))}
                    </div>
                    {tab === 'expenses' && (
                        <div className="pb-2">
                            <Button size="sm" onClick={() => setExpenseModal(true)}>
                                <Plus className="w-4 h-4" /> Add Expense
                            </Button>
                        </div>
                    )}
                </div>

                {/* Overview */}
                {tab === 'overview' && (
                    <div className="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Expense by category pie */}
                        <div>
                            <h3 className="font-semibold text-gray-800 mb-4">Expenses by Category</h3>
                            {pieData.length > 0 ? (
                                <ResponsiveContainer width="100%" height={220}>
                                    <RPieChart>
                                        <Pie data={pieData} cx="50%" cy="50%" innerRadius={60} outerRadius={90}
                                            dataKey="value" paddingAngle={3}>
                                            {pieData.map((_, i) => (
                                                <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip formatter={v => formatCurrency(v)} />
                                        <Legend />
                                    </RPieChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="h-[220px] flex items-center justify-center text-gray-300">
                                    <PieChart className="w-16 h-16" />
                                </div>
                            )}
                        </div>

                        {/* Monthly trend */}
                        <div>
                            <h3 className="font-semibold text-gray-800 mb-4">6-Month Trend</h3>
                            <ResponsiveContainer width="100%" height={220}>
                                <AreaChart data={chartData}>
                                    <defs>
                                        <linearGradient id="incGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#10b981" stopOpacity={0.3} />
                                            <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                                        </linearGradient>
                                        <linearGradient id="expGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#ef4444" stopOpacity={0.3} />
                                            <stop offset="95%" stopColor="#ef4444" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" />
                                    <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                                    <YAxis tick={{ fontSize: 11 }} tickFormatter={v => '৳' + (v/1000).toFixed(0) + 'k'} />
                                    <Tooltip formatter={v => formatCurrency(v)} />
                                    <Legend />
                                    <Area type="monotone" dataKey="income"  stroke="#10b981" fill="url(#incGrad)" strokeWidth={2} />
                                    <Area type="monotone" dataKey="expense" stroke="#ef4444" fill="url(#expGrad)" strokeWidth={2} />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Summary table */}
                        <div className="md:col-span-2">
                            <h3 className="font-semibold text-gray-800 mb-3">This Month Summary</h3>
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                {[
                                    { label: 'Total Revenue',  value: formatCurrency(summary.total_income  ?? 0), color: 'text-emerald-600' },
                                    { label: 'Total Expenses', value: formatCurrency(summary.total_expense ?? 0), color: 'text-red-600' },
                                    { label: 'Gross Profit',   value: formatCurrency(summary.net_profit   ?? 0), color: (summary.net_profit ?? 0) >= 0 ? 'text-violet-600' : 'text-red-600' },
                                    { label: 'Profit Margin',  value: summary.total_income > 0 ? ((summary.net_profit / summary.total_income) * 100).toFixed(1) + '%' : '0%', color: 'text-blue-600' },
                                ].map(s => (
                                    <div key={s.label} className="bg-gray-50 rounded-xl p-4">
                                        <p className={cn('text-xl font-bold', s.color)}>{s.value}</p>
                                        <p className="text-xs text-gray-500 mt-0.5">{s.label}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {/* Expenses tab */}
                {tab === 'expenses' && (
                    <div className="p-4">
                        <div className="flex gap-3 mb-4">
                            <Input type="date" value={dateFrom} onChange={e => setDateFrom(e.target.value)} />
                            <Input type="date" value={dateTo}   onChange={e => setDateTo(e.target.value)} />
                            <Button size="sm" variant="outline" onClick={fetchExpenses}>
                                <Filter className="w-4 h-4" /> Filter
                            </Button>
                        </div>

                        {loading ? (
                            <div className="py-12 text-center text-gray-400">Loading...</div>
                        ) : expenses.length === 0 ? (
                            <div className="py-12 text-center">
                                <Receipt className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                                <p className="text-gray-500">No expenses found</p>
                                <Button className="mt-4" onClick={() => setExpenseModal(true)}>
                                    <Plus className="w-4 h-4" /> Record First Expense
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-left text-xs text-gray-500 border-b border-gray-100">
                                            <th className="pb-2 font-medium">Title</th>
                                            <th className="pb-2 font-medium">Category</th>
                                            <th className="pb-2 font-medium">Date</th>
                                            <th className="pb-2 font-medium">Payment</th>
                                            <th className="pb-2 font-medium text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-50">
                                        {expenses.map(exp => (
                                            <tr key={exp.id} className="hover:bg-gray-50/50">
                                                <td className="py-3 pr-4">
                                                    <p className="font-medium text-gray-800">{exp.title}</p>
                                                    {exp.notes && <p className="text-xs text-gray-400 mt-0.5">{exp.notes}</p>}
                                                </td>
                                                <td className="py-3 pr-4 text-gray-600">{exp.category?.name || '—'}</td>
                                                <td className="py-3 pr-4 text-gray-600">{formatDate(exp.expense_date)}</td>
                                                <td className="py-3 pr-4">
                                                    <span className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full capitalize">
                                                        {exp.payment_method || 'cash'}
                                                    </span>
                                                </td>
                                                <td className="py-3 text-right font-semibold text-red-600">{formatCurrency(exp.amount)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t border-gray-200">
                                            <td colSpan={4} className="py-3 text-sm font-semibold text-gray-700">Total</td>
                                            <td className="py-3 text-right font-bold text-red-600">
                                                {formatCurrency(expenses.reduce((s, e) => s + parseFloat(e.amount), 0))}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                {/* Journal tab */}
                {tab === 'journal' && (
                    <div className="p-4">
                        <div className="flex gap-3 mb-4">
                            <Input type="date" value={dateFrom} onChange={e => setDateFrom(e.target.value)} />
                            <Input type="date" value={dateTo}   onChange={e => setDateTo(e.target.value)} />
                            <Button size="sm" variant="outline" onClick={fetchJournals}>
                                <Filter className="w-4 h-4" /> Filter
                            </Button>
                        </div>
                        {journals.length === 0 ? (
                            <div className="py-12 text-center">
                                <BookOpen className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                                <p className="text-gray-500">No journal entries found</p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {journals.map(j => (
                                    <div key={j.id} className="border border-gray-200 rounded-xl p-4">
                                        <div className="flex items-center justify-between mb-2">
                                            <div>
                                                <span className="text-xs font-mono bg-gray-100 px-2 py-0.5 rounded text-gray-600">{j.entry_number}</span>
                                                <span className="ml-2 text-sm font-medium text-gray-800">{j.description}</span>
                                            </div>
                                            <span className="text-xs text-gray-400">{formatDate(j.entry_date)}</span>
                                        </div>
                                        {j.lines && (
                                            <table className="w-full text-xs mt-2">
                                                <thead>
                                                    <tr className="text-gray-400">
                                                        <th className="text-left pb-1">Account</th>
                                                        <th className="text-right pb-1">Debit</th>
                                                        <th className="text-right pb-1">Credit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {j.lines.map(line => (
                                                        <tr key={line.id}>
                                                            <td className="py-0.5 text-gray-700">{line.account?.name}</td>
                                                            <td className="py-0.5 text-right text-emerald-700">{line.debit > 0 ? formatCurrency(line.debit) : ''}</td>
                                                            <td className="py-0.5 text-right text-red-700">{line.credit > 0 ? formatCurrency(line.credit) : ''}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* Chart of Accounts */}
                {tab === 'accounts' && (
                    <div className="p-4">
                        {accounts.length === 0 ? (
                            <div className="py-12 text-center">
                                <CreditCard className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                                <p className="text-gray-500">No accounts configured</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-left text-xs text-gray-500 border-b border-gray-100">
                                            <th className="pb-2 font-medium">Code</th>
                                            <th className="pb-2 font-medium">Account Name</th>
                                            <th className="pb-2 font-medium">Group</th>
                                            <th className="pb-2 font-medium">Type</th>
                                            <th className="pb-2 font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-50">
                                        {accounts.map(acc => (
                                            <tr key={acc.id} className="hover:bg-gray-50/50">
                                                <td className="py-2.5 pr-4 font-mono text-xs text-gray-500">{acc.code}</td>
                                                <td className="py-2.5 pr-4 font-medium text-gray-800">{acc.name}</td>
                                                <td className="py-2.5 pr-4 text-gray-600">{acc.group?.name || '—'}</td>
                                                <td className="py-2.5 pr-4">
                                                    <span className={cn('text-xs px-2 py-0.5 rounded-full font-medium capitalize',
                                                        acc.group?.account_type === 'asset'     ? 'bg-blue-100 text-blue-700' :
                                                        acc.group?.account_type === 'liability'  ? 'bg-red-100 text-red-700' :
                                                        acc.group?.account_type === 'equity'     ? 'bg-purple-100 text-purple-700' :
                                                        acc.group?.account_type === 'revenue'    ? 'bg-emerald-100 text-emerald-700' :
                                                        acc.group?.account_type === 'expense'    ? 'bg-amber-100 text-amber-700' :
                                                        'bg-gray-100 text-gray-600'
                                                    )}>
                                                        {acc.group?.account_type || '—'}
                                                    </span>
                                                </td>
                                                <td className="py-2.5">
                                                    <span className={cn('text-xs px-2 py-0.5 rounded-full',
                                                        acc.is_active !== false ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'
                                                    )}>
                                                        {acc.is_active !== false ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                {/* P&L */}
                {tab === 'pl' && (
                    <div className="p-5">
                        <div className="flex gap-3 mb-4">
                            <Input type="date" value={dateFrom} onChange={e => setDateFrom(e.target.value)} />
                            <Input type="date" value={dateTo}   onChange={e => setDateTo(e.target.value)} />
                            <Button size="sm" variant="outline" onClick={async () => {
                                setLoading(true);
                                try {
                                    const res = await axios.get('/api/accounting/profit-loss', { params: { from: dateFrom, to: dateTo } });
                                    setPlResult(res.data);
                                } finally { setLoading(false); }
                            }} loading={loading}>
                                <BarChart3 className="w-4 h-4" /> Generate
                            </Button>
                        </div>
                        {!plResult ? (
                            <div className="py-12 text-center">
                                <BarChart3 className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                                <p className="text-gray-500">Select date range and click Generate</p>
                            </div>
                        ) : (
                            <div className="bg-gray-50 rounded-xl p-5">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-left font-semibold text-gray-800 border-b border-gray-200 pb-2">
                                            <th className="pb-3">Account</th>
                                            <th className="pb-3 text-right">Amount (৳)</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        <tr className="bg-emerald-50/50">
                                            <td colSpan={2} className="py-2 px-2 font-semibold text-emerald-800 text-xs uppercase tracking-wide">Revenue</td>
                                        </tr>
                                        {(plResult.revenue_lines || []).map((r, i) => (
                                            <tr key={i}>
                                                <td className="py-2 pl-4 text-gray-700">{r.account}</td>
                                                <td className="py-2 text-right font-medium text-emerald-700">{formatCurrency(r.amount)}</td>
                                            </tr>
                                        ))}
                                        {!(plResult.revenue_lines?.length) && (
                                            <tr>
                                                <td className="py-2 pl-4 text-gray-700">Food & Beverage Sales</td>
                                                <td className="py-2 text-right font-medium text-emerald-700">{formatCurrency(plResult.total_income ?? plResult.income ?? 0)}</td>
                                            </tr>
                                        )}
                                        <tr className="font-semibold text-gray-800">
                                            <td className="py-2">Total Revenue</td>
                                            <td className="py-2 text-right text-emerald-700">{formatCurrency(plResult.total_income ?? plResult.income ?? 0)}</td>
                                        </tr>
                                        <tr className="bg-red-50/50">
                                            <td colSpan={2} className="py-2 px-2 font-semibold text-red-800 text-xs uppercase tracking-wide">Expenses</td>
                                        </tr>
                                        {(plResult.expense_by_cat || plResult.expense_lines || []).map((c, i) => (
                                            <tr key={i}>
                                                <td className="py-2 pl-4 text-gray-700">{c.category || c.account}</td>
                                                <td className="py-2 text-right text-red-600">({formatCurrency(c.total ?? c.amount)})</td>
                                            </tr>
                                        ))}
                                        <tr className="font-semibold text-gray-800 border-t border-gray-200">
                                            <td className="py-2">Total Expenses</td>
                                            <td className="py-2 text-right text-red-600">({formatCurrency(plResult.total_expense ?? plResult.expenses ?? 0)})</td>
                                        </tr>
                                        <tr className="font-bold text-gray-900 border-t-2 border-gray-300 bg-violet-50/50">
                                            <td className="py-3">Net Profit / (Loss)</td>
                                            <td className={cn('py-3 text-right text-lg', (plResult.net_profit ?? 0) >= 0 ? 'text-emerald-700' : 'text-red-700')}>
                                                {formatCurrency(plResult.net_profit ?? 0)}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}
            </div>

            {expenseModal && (
                <ExpenseModal
                    onClose={() => setExpenseModal(false)}
                    onSaved={() => { setExpenseModal(false); fetchExpenses(); }}
                />
            )}
        </AppLayout>
    );
}
