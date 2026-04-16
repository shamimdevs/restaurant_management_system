import { useState, useEffect, useCallback } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import axios from 'axios';
import {
    Package, AlertTriangle, Plus, Search, RefreshCw,
    TrendingDown, TrendingUp, BarChart3, Edit2,
    CheckCircle, X, Save, ChevronRight, History,
    Filter, Download,
} from 'lucide-react';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Badge from '@/Components/UI/Badge';
import { formatCurrency, cn, formatDate } from '@/lib/utils';

// ── Stat Card ──────────────────────────────────────────────────────────────
function StatCard({ icon: Icon, label, value, sub, color }) {
    return (
        <div className="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <div className={cn('w-10 h-10 rounded-xl flex items-center justify-center mb-3', color)}>
                <Icon className="w-5 h-5 text-white" />
            </div>
            <p className="text-2xl font-bold text-gray-900">{value}</p>
            <p className="text-sm text-gray-500">{label}</p>
            {sub && <p className="text-xs text-gray-400 mt-0.5">{sub}</p>}
        </div>
    );
}

// ── Stock Level Bar ────────────────────────────────────────────────────────
function StockBar({ current, min, max }) {
    const pct = max > 0 ? Math.min((current / max) * 100, 100) : 0;
    const color = current <= min ? 'bg-red-500' : current <= min * 1.5 ? 'bg-amber-500' : 'bg-emerald-500';
    return (
        <div className="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div className={cn('h-full rounded-full transition-all', color)} style={{ width: `${pct}%` }} />
        </div>
    );
}

// ── Ingredient Form Modal ──────────────────────────────────────────────────
function IngredientModal({ ingredient, onClose, onSaved }) {
    const isEdit = !!ingredient?.id;
    const [form, setForm] = useState({
        name:               ingredient?.name || '',
        unit:               ingredient?.unit?.name || ingredient?.unit_id || '',
        current_stock:      ingredient?.current_stock || 0,
        min_stock_level:    ingredient?.min_stock_level || 0,
        reorder_level:      ingredient?.reorder_level || 0,
        cost_per_unit:      ingredient?.cost_per_unit || 0,
        supplier_id:        ingredient?.supplier_id || '',
    });
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm(p => ({ ...p, [k]: v }));

    const submit = async () => {
        setSaving(true);
        setErrors({});
        try {
            if (isEdit) {
                await axios.put(`/api/inventory/ingredients/${ingredient.id}`, form);
            } else {
                await axios.post('/api/inventory/ingredients', form);
            }
            onSaved();
        } catch (e) {
            setErrors(e.response?.data?.errors || {});
        } finally {
            setSaving(false);
        }
    };

    return (
        <Modal open onClose={onClose} title={isEdit ? 'Edit Ingredient' : 'Add Ingredient'}>
            <div className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <Input value={form.name} onChange={e => set('name', e.target.value)}
                        placeholder="e.g. Chicken Breast" error={errors.name?.[0]} />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                        <Input value={form.unit} onChange={e => set('unit', e.target.value)} placeholder="kg, litre, pcs" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Cost/Unit (৳)</label>
                        <Input type="number" min={0} step="0.01" value={form.cost_per_unit}
                            onChange={e => set('cost_per_unit', parseFloat(e.target.value))} />
                    </div>
                </div>
                <div className="grid grid-cols-3 gap-3">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Current Stock</label>
                        <Input type="number" min={0} step="0.01" value={form.current_stock}
                            onChange={e => set('current_stock', parseFloat(e.target.value))} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Min Level</label>
                        <Input type="number" min={0} step="0.01" value={form.min_stock_level}
                            onChange={e => set('min_stock_level', parseFloat(e.target.value))} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Reorder At</label>
                        <Input type="number" min={0} step="0.01" value={form.reorder_level}
                            onChange={e => set('reorder_level', parseFloat(e.target.value))} />
                    </div>
                </div>
                <div className="flex gap-3 pt-2">
                    <Button variant="outline" className="flex-1" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>
                        <Save className="w-4 h-4" /> {isEdit ? 'Update' : 'Add Ingredient'}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Adjustment Modal ───────────────────────────────────────────────────────
function AdjustmentModal({ ingredient, onClose, onSaved }) {
    const [form, setForm] = useState({ quantity: '', reason: 'manual_add', notes: '' });
    const [saving, setSaving] = useState(false);

    const submit = async () => {
        setSaving(true);
        try {
            await axios.post('/api/inventory/adjustments', {
                ingredient_id: ingredient.id,
                ...form,
                quantity: parseFloat(form.quantity),
            });
            onSaved();
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to create adjustment');
        } finally { setSaving(false); }
    };

    return (
        <Modal open onClose={onClose} title={`Adjust Stock — ${ingredient?.name}`}>
            <div className="space-y-4">
                <div className="bg-gray-50 rounded-xl p-3 text-sm">
                    <span className="text-gray-500">Current stock: </span>
                    <span className="font-semibold text-gray-800">
                        {ingredient?.current_stock} {ingredient?.unit?.name}
                    </span>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Quantity (+ add / - remove)</label>
                    <Input type="number" step="0.01" value={form.quantity}
                        onChange={e => setForm(p => ({ ...p, quantity: e.target.value }))}
                        placeholder="e.g. 10 or -5" />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <select value={form.reason} onChange={e => setForm(p => ({ ...p, reason: e.target.value }))}
                        className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="manual_add">Manual Addition</option>
                        <option value="purchase">Purchase/Received</option>
                        <option value="spoilage">Spoilage / Waste</option>
                        <option value="damage">Damage</option>
                        <option value="transfer">Transfer</option>
                        <option value="correction">Stock Correction</option>
                    </select>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                    <textarea value={form.notes} onChange={e => setForm(p => ({ ...p, notes: e.target.value }))}
                        rows={2} placeholder="Additional notes..."
                        className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 resize-none" />
                </div>
                <div className="flex gap-3">
                    <Button variant="outline" className="flex-1" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>
                        <Save className="w-4 h-4" /> Apply Adjustment
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Main Page ──────────────────────────────────────────────────────────────
export default function InventoryIndex({ summary = {} }) {
    const [tab, setTab]                 = useState('ingredients'); // ingredients | alerts | movements | recipes
    const [ingredients, setIngredients] = useState([]);
    const [alerts, setAlerts]           = useState([]);
    const [movements, setMovements]     = useState([]);
    const [loading, setLoading]         = useState(false);
    const [search, setSearch]           = useState('');
    const [lowStockOnly, setLowStockOnly] = useState(false);
    const [addModal, setAddModal]       = useState(false);
    const [editItem, setEditItem]       = useState(null);
    const [adjustItem, setAdjustItem]   = useState(null);
    const [page, setPage]               = useState(1);
    const [meta, setMeta]               = useState({});

    const fetchIngredients = useCallback(async () => {
        setLoading(true);
        try {
            const res = await axios.get('/api/inventory/ingredients', {
                params: { search, low_stock: lowStockOnly ? 1 : undefined, page },
            });
            setIngredients(res.data.data);
            setMeta(res.data.meta || {});
        } finally { setLoading(false); }
    }, [search, lowStockOnly, page]);

    const fetchAlerts = useCallback(async () => {
        if (tab !== 'alerts') return;
        const res = await axios.get('/api/inventory/alerts');
        setAlerts(res.data.data || res.data);
    }, [tab]);

    const fetchMovements = useCallback(async () => {
        if (tab !== 'movements') return;
        const res = await axios.get('/api/inventory/movements', { params: { page } });
        setMovements(res.data.data || res.data);
    }, [tab, page]);

    useEffect(() => { fetchIngredients(); }, [fetchIngredients]);
    useEffect(() => { fetchAlerts(); },     [fetchAlerts]);
    useEffect(() => { fetchMovements(); },  [fetchMovements]);

    const resolveAlert = async (id) => {
        await axios.patch(`/api/inventory/alerts/${id}/resolve`);
        fetchAlerts();
    };

    return (
        <AppLayout title="Inventory Management">
            {/* ── Stats ── */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <StatCard icon={Package}       label="Total Ingredients" value={summary.total_ingredients ?? '—'}     color="bg-violet-500" />
                <StatCard icon={AlertTriangle} label="Low Stock Items"   value={summary.low_stock_count ?? '—'}      color="bg-red-500" />
                <StatCard icon={TrendingDown}  label="Reorder Needed"    value={summary.reorder_count ?? '—'}        color="bg-amber-500" />
                <StatCard icon={BarChart3}     label="Stock Value"       value={formatCurrency(summary.total_value ?? 0)} color="bg-blue-500" />
            </div>

            {/* ── Tabs ── */}
            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm">
                <div className="flex items-center justify-between px-4 pt-4 pb-0 border-b border-gray-100">
                    <div className="flex gap-1">
                        {[
                            { key: 'ingredients', label: 'Ingredients' },
                            { key: 'alerts',      label: 'Alerts' },
                            { key: 'movements',   label: 'Movements' },
                            { key: 'recipes',     label: 'Recipes' },
                        ].map(t => (
                            <button key={t.key} onClick={() => setTab(t.key)}
                                className={cn(
                                    'px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                                    tab === t.key ? 'border-violet-600 text-violet-600' : 'border-transparent text-gray-500 hover:text-gray-700'
                                )}>
                                {t.label}
                                {t.key === 'alerts' && alerts.length > 0 && (
                                    <span className="ml-1.5 bg-red-100 text-red-700 text-xs px-1.5 py-0.5 rounded-full">
                                        {alerts.length}
                                    </span>
                                )}
                            </button>
                        ))}
                    </div>
                    {tab === 'ingredients' && (
                        <Button size="sm" onClick={() => setAddModal(true)}>
                            <Plus className="w-4 h-4" /> Add
                        </Button>
                    )}
                </div>

                {/* ── Ingredients tab ── */}
                {tab === 'ingredients' && (
                    <div className="p-4">
                        <div className="flex gap-3 mb-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input value={search} onChange={e => { setSearch(e.target.value); setPage(1); }}
                                    placeholder="Search ingredients..."
                                    className="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500" />
                            </div>
                            <button onClick={() => { setLowStockOnly(p => !p); setPage(1); }}
                                className={cn(
                                    'flex items-center gap-2 px-3 py-2 rounded-xl border text-sm font-medium transition-colors',
                                    lowStockOnly ? 'bg-red-50 border-red-200 text-red-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                )}>
                                <AlertTriangle className="w-4 h-4" />
                                Low Stock
                            </button>
                        </div>

                        {loading ? (
                            <div className="py-12 text-center text-gray-400">Loading...</div>
                        ) : ingredients.length === 0 ? (
                            <div className="py-12 text-center">
                                <Package className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                <p className="text-gray-500">No ingredients found</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="text-left text-xs text-gray-500 border-b border-gray-100">
                                            <th className="pb-2 font-medium">Ingredient</th>
                                            <th className="pb-2 font-medium">Stock</th>
                                            <th className="pb-2 font-medium">Min Level</th>
                                            <th className="pb-2 font-medium">Cost/Unit</th>
                                            <th className="pb-2 font-medium">Status</th>
                                            <th className="pb-2 font-medium text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-50">
                                        {ingredients.map(item => {
                                            const isLow = item.current_stock <= item.min_stock_level;
                                            const isReorder = item.current_stock <= item.reorder_level;
                                            return (
                                                <tr key={item.id} className="hover:bg-gray-50/50 transition-colors">
                                                    <td className="py-3 pr-4">
                                                        <p className="font-medium text-gray-800 text-sm">{item.name}</p>
                                                        <p className="text-xs text-gray-400">{item.unit?.name || item.unit}</p>
                                                    </td>
                                                    <td className="py-3 pr-4">
                                                        <p className={cn('text-sm font-semibold', isLow ? 'text-red-600' : 'text-gray-800')}>
                                                            {item.current_stock}
                                                        </p>
                                                        <StockBar current={item.current_stock} min={item.min_stock_level} max={item.current_stock * 2 || 100} />
                                                    </td>
                                                    <td className="py-3 pr-4 text-sm text-gray-600">{item.min_stock_level}</td>
                                                    <td className="py-3 pr-4 text-sm text-gray-600">৳{item.cost_per_unit}</td>
                                                    <td className="py-3 pr-4">
                                                        {isLow ? (
                                                            <span className="inline-flex items-center gap-1 text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full">
                                                                <AlertTriangle className="w-3 h-3" /> Low
                                                            </span>
                                                        ) : isReorder ? (
                                                            <span className="inline-flex items-center gap-1 text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">
                                                                Reorder
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center gap-1 text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">
                                                                <CheckCircle className="w-3 h-3" /> OK
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="py-3 text-right">
                                                        <div className="flex items-center justify-end gap-1">
                                                            <button onClick={() => setAdjustItem(item)}
                                                                className="p-1.5 text-gray-400 hover:text-violet-600 hover:bg-violet-50 rounded-lg transition-colors"
                                                                title="Adjust Stock">
                                                                <RefreshCw className="w-4 h-4" />
                                                            </button>
                                                            <button onClick={() => setEditItem(item)}
                                                                className="p-1.5 text-gray-400 hover:text-violet-600 hover:bg-violet-50 rounded-lg transition-colors"
                                                                title="Edit">
                                                                <Edit2 className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {/* Pagination */}
                        {meta.last_page > 1 && (
                            <div className="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                                <p className="text-sm text-gray-500">
                                    Showing {meta.from}–{meta.to} of {meta.total}
                                </p>
                                <div className="flex gap-2">
                                    <Button size="sm" variant="outline" disabled={page === 1} onClick={() => setPage(p => p - 1)}>Previous</Button>
                                    <Button size="sm" variant="outline" disabled={page === meta.last_page} onClick={() => setPage(p => p + 1)}>Next</Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* ── Alerts tab ── */}
                {tab === 'alerts' && (
                    <div className="p-4">
                        {alerts.length === 0 ? (
                            <div className="py-12 text-center">
                                <CheckCircle className="w-12 h-12 text-emerald-300 mx-auto mb-3" />
                                <p className="text-gray-500 font-medium">No active alerts</p>
                                <p className="text-gray-400 text-sm">All stock levels are healthy</p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {alerts.map(alert => (
                                    <div key={alert.id}
                                        className="flex items-center gap-4 p-4 bg-red-50 border border-red-100 rounded-xl">
                                        <AlertTriangle className="w-5 h-5 text-red-500 flex-shrink-0" />
                                        <div className="flex-1 min-w-0">
                                            <p className="font-medium text-gray-800 text-sm">{alert.ingredient?.name}</p>
                                            <p className="text-xs text-gray-500 mt-0.5">
                                                Current: <span className="font-semibold text-red-600">{alert.current_level}</span>
                                                {' '}· Min: {alert.min_level}
                                                {' '}· Reorder: {alert.reorder_level}
                                            </p>
                                        </div>
                                        <button onClick={() => resolveAlert(alert.id)}
                                            className="text-xs bg-white border border-gray-200 text-gray-600 hover:text-emerald-600 px-3 py-1.5 rounded-lg transition-colors">
                                            Resolve
                                        </button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* ── Movements tab ── */}
                {tab === 'movements' && (
                    <div className="p-4">
                        {movements.length === 0 ? (
                            <div className="py-12 text-center text-gray-400">
                                <History className="w-12 h-12 mx-auto mb-3 text-gray-200" />
                                No stock movements yet
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-left text-xs text-gray-500 border-b border-gray-100">
                                            <th className="pb-2 font-medium">Ingredient</th>
                                            <th className="pb-2 font-medium">Type</th>
                                            <th className="pb-2 font-medium">Qty</th>
                                            <th className="pb-2 font-medium">Balance</th>
                                            <th className="pb-2 font-medium">Reference</th>
                                            <th className="pb-2 font-medium">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-50">
                                        {movements.map(m => (
                                            <tr key={m.id} className="hover:bg-gray-50/50">
                                                <td className="py-2.5 pr-4 font-medium text-gray-800">{m.ingredient?.name}</td>
                                                <td className="py-2.5 pr-4">
                                                    <span className={cn(
                                                        'text-xs px-2 py-0.5 rounded-full capitalize',
                                                        m.movement_type === 'in' ? 'bg-emerald-100 text-emerald-700' :
                                                        m.movement_type === 'out' ? 'bg-red-100 text-red-700' :
                                                        'bg-gray-100 text-gray-700'
                                                    )}>
                                                        {m.movement_type}
                                                    </span>
                                                </td>
                                                <td className={cn('py-2.5 pr-4 font-semibold',
                                                    m.movement_type === 'in' ? 'text-emerald-600' : 'text-red-600')}>
                                                    {m.movement_type === 'in' ? '+' : '-'}{Math.abs(m.quantity)}
                                                </td>
                                                <td className="py-2.5 pr-4 text-gray-600">{m.balance_after}</td>
                                                <td className="py-2.5 pr-4 text-gray-500 text-xs">{m.reference_type}</td>
                                                <td className="py-2.5 text-gray-400 text-xs">{formatDate(m.created_at)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                {/* ── Recipes tab ── */}
                {tab === 'recipes' && (
                    <div className="p-4 text-center py-12">
                        <Package className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                        <p className="text-gray-500 font-medium">Recipe Management</p>
                        <p className="text-gray-400 text-sm mt-1">Link menu items to ingredients for auto stock deduction</p>
                        <Button className="mt-4" onClick={() => {}}>
                            <Plus className="w-4 h-4" /> Create Recipe
                        </Button>
                    </div>
                )}
            </div>

            {/* ── Modals ── */}
            {addModal && (
                <IngredientModal
                    onClose={() => setAddModal(false)}
                    onSaved={() => { setAddModal(false); fetchIngredients(); }}
                />
            )}
            {editItem && (
                <IngredientModal
                    ingredient={editItem}
                    onClose={() => setEditItem(null)}
                    onSaved={() => { setEditItem(null); fetchIngredients(); }}
                />
            )}
            {adjustItem && (
                <AdjustmentModal
                    ingredient={adjustItem}
                    onClose={() => setAdjustItem(null)}
                    onSaved={() => { setAdjustItem(null); fetchIngredients(); fetchAlerts(); }}
                />
            )}
        </AppLayout>
    );
}
