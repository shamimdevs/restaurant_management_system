import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { router } from '@inertiajs/react';
import axios from 'axios';
import {
    Tag, Plus, Gift, Star, Percent, DollarSign,
    ToggleLeft, ToggleRight, Copy, RefreshCw,
    Calendar, Users, AlertCircle, Save, Zap,
} from 'lucide-react';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import { cn, formatCurrency, formatDate } from '@/lib/utils';

// ── Promotion type badge ───────────────────────────────────────────────────
const PROMO_TYPES = {
    percentage: { label: 'Percentage %',  color: 'bg-violet-100 text-violet-700', icon: Percent },
    fixed:      { label: 'Fixed Amount',  color: 'bg-blue-100 text-blue-700',     icon: DollarSign },
    bxgy:       { label: 'Buy X Get Y',   color: 'bg-emerald-100 text-emerald-700', icon: Gift },
    free_item:  { label: 'Free Item',     color: 'bg-amber-100 text-amber-700',   icon: Zap },
};

function TypeBadge({ type }) {
    const t = PROMO_TYPES[type] ?? PROMO_TYPES.fixed;
    return (
        <span className={cn('inline-flex items-center gap-1 text-xs px-2.5 py-0.5 rounded-full font-medium', t.color)}>
            <t.icon className="w-3 h-3" />
            {t.label}
        </span>
    );
}

// ── Promotion Card ─────────────────────────────────────────────────────────
function PromoCard({ promo, onToggle }) {
    return (
        <div className={cn(
            'bg-white border-2 rounded-2xl p-4 shadow-sm transition-all',
            promo.is_active ? 'border-violet-200' : 'border-gray-200 opacity-70'
        )}>
            <div className="flex items-start justify-between mb-3">
                <TypeBadge type={promo.type} />
                <button onClick={() => onToggle(promo)}
                    className={cn('transition-colors', promo.is_active ? 'text-violet-600' : 'text-gray-300')}>
                    {promo.is_active ? <ToggleRight className="w-8 h-8" /> : <ToggleLeft className="w-8 h-8" />}
                </button>
            </div>
            <h3 className="font-semibold text-gray-900 mb-1">{promo.name}</h3>
            <p className="text-2xl font-bold text-violet-600 mb-2">
                {promo.type === 'percentage' ? `${promo.discount_value}% OFF` :
                 promo.type === 'fixed' ? `৳${promo.discount_value} OFF` :
                 promo.type === 'bxgy' ? 'B1G1 Deal' : 'Free Item'}
            </p>
            {promo.min_order_amount > 0 && (
                <p className="text-xs text-gray-500">Min order: {formatCurrency(promo.min_order_amount)}</p>
            )}
            <div className="flex items-center gap-3 mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500">
                <span className="flex items-center gap-1">
                    <Calendar className="w-3 h-3" />
                    {formatDate(promo.start_date)} – {formatDate(promo.end_date)}
                </span>
            </div>
        </div>
    );
}

// ── Coupon Row ─────────────────────────────────────────────────────────────
function CouponRow({ coupon, onToggle }) {
    const copy = () => {
        navigator.clipboard.writeText(coupon.code);
    };

    return (
        <tr className={cn('hover:bg-gray-50/50', !coupon.is_active && 'opacity-60')}>
            <td className="px-4 py-3">
                <div className="flex items-center gap-2">
                    <code className="bg-gray-100 text-violet-700 font-mono font-bold px-2 py-0.5 rounded text-sm">
                        {coupon.code}
                    </code>
                    <button onClick={copy} className="text-gray-400 hover:text-violet-600 transition-colors">
                        <Copy className="w-3.5 h-3.5" />
                    </button>
                </div>
            </td>
            <td className="px-4 py-3">
                <TypeBadge type={coupon.discount_type} />
            </td>
            <td className="px-4 py-3 font-semibold text-gray-800">
                {coupon.discount_type === 'percentage' ? `${coupon.discount_value}%` : formatCurrency(coupon.discount_value)}
            </td>
            <td className="px-4 py-3 text-gray-600 text-sm">
                {coupon.min_order_amount > 0 ? formatCurrency(coupon.min_order_amount) : '—'}
            </td>
            <td className="px-4 py-3 text-sm text-gray-600">
                {coupon.usages_count ?? 0} / {coupon.usage_limit ?? '∞'}
            </td>
            <td className="px-4 py-3 text-sm text-gray-500">
                {coupon.expires_at ? formatDate(coupon.expires_at) : 'No expiry'}
            </td>
            <td className="px-4 py-3">
                <span className={cn('text-xs px-2 py-0.5 rounded-full font-medium',
                    coupon.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500')}>
                    {coupon.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td className="px-4 py-3">
                <button onClick={() => onToggle(coupon)}
                    className={cn('transition-colors', coupon.is_active ? 'text-violet-600' : 'text-gray-300')}>
                    {coupon.is_active ? <ToggleRight className="w-6 h-6" /> : <ToggleLeft className="w-6 h-6" />}
                </button>
            </td>
        </tr>
    );
}

// ── Add Promotion Modal ────────────────────────────────────────────────────
function PromoModal({ onClose, onSaved }) {
    const [form, setForm] = useState({
        name:             '',
        type:             'percentage',
        discount_value:   '',
        min_order_amount: '',
        start_date:       new Date().toISOString().split('T')[0],
        end_date:         '',
        is_active:        true,
    });
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm(p => ({ ...p, [k]: v }));

    const submit = async () => {
        setSaving(true); setErrors({});
        try {
            await axios.post('/api/promotions', form);
            onSaved();
        } catch (e) { setErrors(e.response?.data?.errors || {}); }
        finally { setSaving(false); }
    };

    return (
        <Modal open onClose={onClose} title="Create Promotion">
            <div className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <Input value={form.name} onChange={e => set('name', e.target.value)}
                        error={errors.name?.[0]} placeholder="e.g. Weekend Special" />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                        <select value={form.type} onChange={e => set('type', e.target.value)}
                            className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            {Object.entries(PROMO_TYPES).map(([k, t]) => (
                                <option key={k} value={k}>{t.label}</option>
                            ))}
                        </select>
                    </div>
                    {(form.type === 'percentage' || form.type === 'fixed') && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                {form.type === 'percentage' ? 'Discount %' : 'Amount (৳)'}
                            </label>
                            <Input type="number" min={0} step="0.01" value={form.discount_value}
                                onChange={e => set('discount_value', e.target.value)} />
                        </div>
                    )}
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Minimum Order (৳)</label>
                    <Input type="number" min={0} value={form.min_order_amount}
                        onChange={e => set('min_order_amount', e.target.value)} placeholder="0 = no minimum" />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                        <Input type="date" value={form.start_date} onChange={e => set('start_date', e.target.value)} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
                        <Input type="date" value={form.end_date} onChange={e => set('end_date', e.target.value)}
                            error={errors.end_date?.[0]} />
                    </div>
                </div>
                <div className="flex gap-3 pt-2">
                    <Button variant="outline" className="flex-1" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>
                        <Save className="w-4 h-4" /> Create
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Add Coupon Modal ───────────────────────────────────────────────────────
function CouponModal({ onClose, onSaved }) {
    const [form, setForm] = useState({
        code:                  '',
        discount_type:         'percentage',
        discount_value:        '',
        min_order_amount:      '',
        max_discount_amount:   '',
        usage_limit:           '',
        usage_limit_per_user:  1,
        expires_at:            '',
        is_active:             true,
    });
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm(p => ({ ...p, [k]: v }));

    const genCode = async () => {
        const res = await axios.get('/api/promotions/generate-code');
        set('code', res.data.code);
    };

    const submit = async () => {
        setSaving(true); setErrors({});
        try {
            await axios.post('/api/promotions/coupons', form);
            onSaved();
        } catch (e) { setErrors(e.response?.data?.errors || {}); }
        finally { setSaving(false); }
    };

    return (
        <Modal open onClose={onClose} title="Create Coupon">
            <div className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Coupon Code *</label>
                    <div className="flex gap-2">
                        <Input value={form.code} onChange={e => set('code', e.target.value.toUpperCase())}
                            error={errors.code?.[0]} placeholder="SAVE20" className="flex-1 font-mono" />
                        <Button size="sm" variant="outline" onClick={genCode} type="button">
                            <RefreshCw className="w-4 h-4" />
                        </Button>
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                        <select value={form.discount_type} onChange={e => set('discount_type', e.target.value)}
                            className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="percentage">Percentage %</option>
                            <option value="fixed">Fixed Amount ৳</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            {form.discount_type === 'percentage' ? 'Discount %' : 'Amount (৳)'}
                        </label>
                        <Input type="number" min={0} step="0.01" value={form.discount_value}
                            onChange={e => set('discount_value', e.target.value)} />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Min Order (৳)</label>
                        <Input type="number" min={0} value={form.min_order_amount}
                            onChange={e => set('min_order_amount', e.target.value)} placeholder="0" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Max Discount (৳)</label>
                        <Input type="number" min={0} value={form.max_discount_amount}
                            onChange={e => set('max_discount_amount', e.target.value)} placeholder="No cap" />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Usage Limit</label>
                        <Input type="number" min={1} value={form.usage_limit}
                            onChange={e => set('usage_limit', e.target.value)} placeholder="Unlimited" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Expires At</label>
                        <Input type="datetime-local" value={form.expires_at}
                            onChange={e => set('expires_at', e.target.value)} />
                    </div>
                </div>
                <div className="flex gap-3 pt-2">
                    <Button variant="outline" className="flex-1" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>
                        <Save className="w-4 h-4" /> Create Coupon
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Main Page ──────────────────────────────────────────────────────────────
export default function PromotionsIndex({ promotions = [], coupons = [], loyalty }) {
    const [tab, setTab]           = useState('promotions');
    const [promoModal, setPromoModal]   = useState(false);
    const [couponModal, setCouponModal] = useState(false);
    const [localPromos, setLocalPromos] = useState(promotions);
    const [localCoupons, setLocalCoupons] = useState(coupons);
    const [loyaltyForm, setLoyaltyForm] = useState({
        points_per_taka:   loyalty?.points_per_taka   ?? 1,
        taka_per_point:    loyalty?.taka_per_point    ?? 0.01,
        min_redeem_points: loyalty?.min_redeem_points ?? 100,
        expiry_days:       loyalty?.expiry_days       ?? 365,
        is_active:         loyalty?.is_active         ?? true,
    });
    const [loyaltySaving, setLoyaltySaving] = useState(false);

    const togglePromo = async (promo) => {
        await axios.patch(`/api/promotions/${promo.id}/toggle`);
        setLocalPromos(ps => ps.map(p => p.id === promo.id ? { ...p, is_active: !p.is_active } : p));
    };

    const toggleCoupon = async (coupon) => {
        await axios.patch(`/api/promotions/coupons/${coupon.id}/toggle`);
        setLocalCoupons(cs => cs.map(c => c.id === coupon.id ? { ...c, is_active: !c.is_active } : c));
    };

    const saveLoyalty = async () => {
        setLoyaltySaving(true);
        try {
            await axios.post('/api/promotions/loyalty', loyaltyForm);
        } finally { setLoyaltySaving(false); }
    };

    const reload = () => router.reload({ only: ['promotions', 'coupons', 'loyalty'] });

    // Stats
    const activePromos  = localPromos.filter(p => p.is_active).length;
    const activeCoupons = localCoupons.filter(c => c.is_active).length;
    const totalUses     = localCoupons.reduce((s, c) => s + (c.usages_count ?? 0), 0);

    return (
        <AppLayout title="Offers & Promotions">
            {/* Stats */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                {[
                    { label: 'Active Promotions', value: activePromos,  icon: Tag,   bg: 'bg-violet-500' },
                    { label: 'Active Coupons',    value: activeCoupons, icon: Percent, bg: 'bg-blue-500' },
                    { label: 'Total Coupon Uses', value: totalUses,     icon: Users,  bg: 'bg-emerald-500' },
                    { label: 'Loyalty Points',    value: loyaltyForm.is_active ? 'On' : 'Off', icon: Star, bg: 'bg-amber-500' },
                ].map(s => (
                    <div key={s.label} className="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
                        <div className={cn('w-10 h-10 rounded-xl flex items-center justify-center mb-3', s.bg)}>
                            <s.icon className="w-5 h-5 text-white" />
                        </div>
                        <p className="text-2xl font-bold text-gray-900">{s.value}</p>
                        <p className="text-sm text-gray-500">{s.label}</p>
                    </div>
                ))}
            </div>

            {/* Main card */}
            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm">
                {/* Tabs */}
                <div className="flex items-center justify-between px-4 pt-4 border-b border-gray-100">
                    <div className="flex gap-1">
                        {[
                            { key: 'promotions', label: 'Promotions' },
                            { key: 'coupons',    label: 'Coupon Codes' },
                            { key: 'loyalty',    label: 'Loyalty Program' },
                        ].map(t => (
                            <button key={t.key} onClick={() => setTab(t.key)}
                                className={cn(
                                    'px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                                    tab === t.key ? 'border-violet-600 text-violet-600' : 'border-transparent text-gray-500 hover:text-gray-700'
                                )}>
                                {t.label}
                            </button>
                        ))}
                    </div>
                    <div className="pb-2">
                        {tab === 'promotions' && (
                            <Button size="sm" onClick={() => setPromoModal(true)}>
                                <Plus className="w-4 h-4" /> Create
                            </Button>
                        )}
                        {tab === 'coupons' && (
                            <Button size="sm" onClick={() => setCouponModal(true)}>
                                <Plus className="w-4 h-4" /> Add Coupon
                            </Button>
                        )}
                    </div>
                </div>

                {/* Promotions grid */}
                {tab === 'promotions' && (
                    <div className="p-4">
                        {localPromos.length === 0 ? (
                            <div className="py-12 text-center">
                                <Tag className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                                <p className="text-gray-500 font-medium">No promotions yet</p>
                                <Button className="mt-4" onClick={() => setPromoModal(true)}>
                                    <Plus className="w-4 h-4" /> Create First Promotion
                                </Button>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                {localPromos.map(p => (
                                    <PromoCard key={p.id} promo={p} onToggle={togglePromo} />
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* Coupons table */}
                {tab === 'coupons' && (
                    <div className="p-0">
                        {localCoupons.length === 0 ? (
                            <div className="py-12 text-center">
                                <Percent className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                                <p className="text-gray-500 font-medium">No coupons yet</p>
                                <Button className="mt-4" onClick={() => setCouponModal(true)}>
                                    <Plus className="w-4 h-4" /> Create First Coupon
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="text-left text-xs text-gray-500 border-b border-gray-100">
                                            <th className="px-4 py-3 font-medium">Code</th>
                                            <th className="px-4 py-3 font-medium">Type</th>
                                            <th className="px-4 py-3 font-medium">Discount</th>
                                            <th className="px-4 py-3 font-medium">Min Order</th>
                                            <th className="px-4 py-3 font-medium">Uses</th>
                                            <th className="px-4 py-3 font-medium">Expires</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 font-medium"></th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-50">
                                        {localCoupons.map(c => (
                                            <CouponRow key={c.id} coupon={c} onToggle={toggleCoupon} />
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                {/* Loyalty */}
                {tab === 'loyalty' && (
                    <div className="p-5 max-w-lg">
                        <div className="flex items-center gap-3 mb-5">
                            <div className="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center">
                                <Star className="w-6 h-6 text-amber-600" />
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900">Loyalty Points Program</h3>
                                <p className="text-sm text-gray-500">Reward customers on every purchase</p>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Points per ৳1 spent
                                    </label>
                                    <Input type="number" min={0} step="0.01"
                                        value={loyaltyForm.points_per_taka}
                                        onChange={e => setLoyaltyForm(p => ({ ...p, points_per_taka: parseFloat(e.target.value) }))} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        ৳ value per point
                                    </label>
                                    <Input type="number" min={0} step="0.001"
                                        value={loyaltyForm.taka_per_point}
                                        onChange={e => setLoyaltyForm(p => ({ ...p, taka_per_point: parseFloat(e.target.value) }))} />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Min redeem points</label>
                                    <Input type="number" min={1}
                                        value={loyaltyForm.min_redeem_points}
                                        onChange={e => setLoyaltyForm(p => ({ ...p, min_redeem_points: parseInt(e.target.value) }))} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Points expiry (days)</label>
                                    <Input type="number" min={1}
                                        value={loyaltyForm.expiry_days}
                                        onChange={e => setLoyaltyForm(p => ({ ...p, expiry_days: parseInt(e.target.value) }))} />
                                </div>
                            </div>

                            <div className="bg-amber-50 rounded-xl p-4 text-sm">
                                <p className="font-medium text-amber-800 mb-1">Example</p>
                                <p className="text-amber-700">
                                    Customer spends ৳1,000 → earns{' '}
                                    <strong>{(1000 * loyaltyForm.points_per_taka).toFixed(0)} pts</strong>
                                    {' '}→ worth{' '}
                                    <strong>৳{(1000 * loyaltyForm.points_per_taka * loyaltyForm.taka_per_point).toFixed(2)}</strong>
                                </p>
                            </div>

                            <div className="flex items-center gap-3">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" checked={loyaltyForm.is_active}
                                        onChange={e => setLoyaltyForm(p => ({ ...p, is_active: e.target.checked }))}
                                        className="w-4 h-4 text-violet-600 rounded" />
                                    <span className="text-sm font-medium text-gray-700">Enable Loyalty Program</span>
                                </label>
                            </div>

                            <Button onClick={saveLoyalty} loading={loyaltySaving}>
                                <Save className="w-4 h-4" /> Save Loyalty Settings
                            </Button>
                        </div>
                    </div>
                )}
            </div>

            {/* Modals */}
            {promoModal  && <PromoModal  onClose={() => setPromoModal(false)}  onSaved={() => { setPromoModal(false);  reload(); }} />}
            {couponModal && <CouponModal onClose={() => setCouponModal(false)} onSaved={() => { setCouponModal(false); reload(); }} />}
        </AppLayout>
    );
}
