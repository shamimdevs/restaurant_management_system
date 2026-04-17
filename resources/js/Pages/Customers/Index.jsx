import { useState, useEffect, useCallback } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import axios from 'axios';
import {
    Users, Search, Plus, Star, Phone, Mail,
    MapPin, ShoppingBag, TrendingUp, Gift,
    ChevronRight, Eye, Edit2, Save, X, Award,
} from 'lucide-react';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import { cn, formatCurrency, formatDate } from '@/lib/utils';

// ── Segment badge ──────────────────────────────────────────────────────────
const SEGMENTS = {
    vip:      { label: 'VIP',      color: 'bg-violet-100 text-violet-700' },
    regular:  { label: 'Regular',  color: 'bg-blue-100 text-blue-700' },
    new:      { label: 'New',      color: 'bg-emerald-100 text-emerald-700' },
    inactive: { label: 'Inactive', color: 'bg-gray-100 text-gray-600' },
};

function SegmentBadge({ segment }) {
    const s = SEGMENTS[segment] ?? SEGMENTS.new;
    return <span className={cn('text-xs px-2 py-0.5 rounded-full font-medium', s.color)}>{s.label}</span>;
}

// ── Customer Form ──────────────────────────────────────────────────────────
function CustomerModal({ customer, onClose, onSaved }) {
    const isEdit = !!customer?.id;
    const [form, setForm] = useState({
        name:          customer?.name || '',
        phone:         customer?.phone || '',
        email:         customer?.email || '',
        gender:        customer?.gender || '',
        date_of_birth: customer?.date_of_birth || '',
        address:       customer?.address || '',
        area:          customer?.area || '',
        city:          customer?.city || 'Dhaka',
    });
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm(p => ({ ...p, [k]: v }));

    const submit = async () => {
        setSaving(true); setErrors({});
        try {
            if (isEdit) {
                await axios.put(`/api/customers/${customer.id}`, form);
            } else {
                await axios.post('/api/customers', form);
            }
            onSaved();
        } catch (e) { setErrors(e.response?.data?.errors || {}); }
        finally { setSaving(false); }
    };

    return (
        <Modal open onClose={onClose} title={isEdit ? 'Edit Customer' : 'Add Customer'}>
            <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <Input value={form.name} onChange={e => set('name', e.target.value)} error={errors.name?.[0]} placeholder="Customer name" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                        <Input value={form.phone} onChange={e => set('phone', e.target.value)} error={errors.phone?.[0]} placeholder="01XXXXXXXXX" />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <Input type="email" value={form.email} onChange={e => set('email', e.target.value)} placeholder="email@example.com" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select value={form.gender} onChange={e => set('gender', e.target.value)}
                            className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                        <Input type="date" value={form.date_of_birth} onChange={e => set('date_of_birth', e.target.value)} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">City</label>
                        <Input value={form.city} onChange={e => set('city', e.target.value)} placeholder="Dhaka" />
                    </div>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <Input value={form.address} onChange={e => set('address', e.target.value)} placeholder="Street address" />
                </div>
                <div className="flex gap-3 pt-2">
                    <Button variant="outline" className="flex-1" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>
                        <Save className="w-4 h-4" /> {isEdit ? 'Update' : 'Save Customer'}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Customer Profile Drawer ────────────────────────────────────────────────
function CustomerProfile({ customer, onClose }) {
    const [profile, setProfile] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!customer) return;
        setLoading(true);
        axios.get(`/api/customers/${customer.id}`)
            .then(r => setProfile(r.data))
            .finally(() => setLoading(false));
    }, [customer?.id]);

    return (
        <Modal open={!!customer} onClose={onClose} title="Customer Profile" size="lg">
            {loading ? (
                <div className="py-12 text-center text-gray-400">Loading profile...</div>
            ) : profile ? (
                <div className="space-y-5">
                    {/* Header */}
                    <div className="flex items-center gap-4">
                        <div className="w-16 h-16 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0">
                            {profile.customer.name[0].toUpperCase()}
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h3 className="font-bold text-gray-900 text-lg">{profile.customer.name}</h3>
                                <SegmentBadge segment={profile.customer.segment} />
                            </div>
                            <p className="text-sm text-gray-500 flex items-center gap-1 mt-0.5">
                                <Phone className="w-3.5 h-3.5" /> {profile.customer.phone}
                            </p>
                            {profile.customer.email && (
                                <p className="text-sm text-gray-500 flex items-center gap-1">
                                    <Mail className="w-3.5 h-3.5" /> {profile.customer.email}
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Stats */}
                    <div className="grid grid-cols-3 gap-3">
                        {[
                            { label: 'Total Orders', value: profile.customer.total_orders },
                            { label: 'Total Spent',  value: formatCurrency(profile.customer.total_spent) },
                            { label: 'Loyalty Points', value: `${profile.customer.loyalty_points} pts` },
                        ].map(s => (
                            <div key={s.label} className="bg-gray-50 rounded-xl p-3 text-center">
                                <p className="text-lg font-bold text-gray-900">{s.value}</p>
                                <p className="text-xs text-gray-500">{s.label}</p>
                            </div>
                        ))}
                    </div>

                    {/* Recent orders */}
                    {profile.order_history?.length > 0 && (
                        <div>
                            <h4 className="font-semibold text-gray-800 mb-3 text-sm">Recent Orders</h4>
                            <div className="space-y-2 max-h-48 overflow-y-auto">
                                {profile.order_history.slice(0, 5).map(order => (
                                    <div key={order.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-xl text-sm">
                                        <div>
                                            <p className="font-medium text-gray-800">{order.order_number}</p>
                                            <p className="text-xs text-gray-400">{formatDate(order.created_at)}</p>
                                        </div>
                                        <p className="font-semibold text-gray-900">{formatCurrency(order.total_amount)}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Point history */}
                    {profile.customer.point_transactions?.length > 0 && (
                        <div>
                            <h4 className="font-semibold text-gray-800 mb-3 text-sm flex items-center gap-2">
                                <Award className="w-4 h-4 text-amber-500" /> Loyalty Points
                            </h4>
                            <div className="space-y-2 max-h-36 overflow-y-auto">
                                {profile.customer.point_transactions.map(pt => (
                                    <div key={pt.id} className="flex items-center justify-between text-sm p-2.5 bg-amber-50 rounded-lg">
                                        <span className="text-gray-600">{pt.description}</span>
                                        <span className={cn('font-semibold', pt.points > 0 ? 'text-emerald-600' : 'text-red-600')}>
                                            {pt.points > 0 ? '+' : ''}{pt.points} pts
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            ) : null}
        </Modal>
    );
}

// ── Main Page ──────────────────────────────────────────────────────────────
export default function CustomersIndex() {
    const [customers, setCustomers] = useState([]);
    const [loading, setLoading]     = useState(false);
    const [search, setSearch]       = useState('');
    const [segment, setSegment]     = useState('');
    const [page, setPage]           = useState(1);
    const [meta, setMeta]           = useState({});
    const [stats, setStats]         = useState({ total: '—', vip: '—', active: '—', with_points: '—' });
    const [addModal, setAddModal]   = useState(false);
    const [editItem, setEditItem]   = useState(null);
    const [viewItem, setViewItem]   = useState(null);

    const fetch = useCallback(async () => {
        setLoading(true);
        try {
            const res = await axios.get('/api/customers', { params: { search, segment, page } });
            setCustomers(res.data.data);
            setMeta(res.data.meta || {});
        } finally { setLoading(false); }
    }, [search, segment, page]);

    useEffect(() => {
        axios.get('/api/customers/stats').then(r => setStats(r.data)).catch(() => {});
    }, []);

    useEffect(() => { fetch(); }, [fetch]);

    return (
        <AppLayout title="Customer Management">
            {/* Stats */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                {[
                    { label: 'Total Customers',  icon: Users,      color: 'bg-violet-500', value: stats.total },
                    { label: 'VIP Customers',    icon: Star,       color: 'bg-amber-500',  value: stats.vip },
                    { label: 'Active (30d)',      icon: TrendingUp, color: 'bg-emerald-500',value: stats.active },
                    { label: 'With Loyalty Pts', icon: Gift,       color: 'bg-blue-500',   value: stats.with_points },
                ].map((s, i) => (
                    <div key={i} className="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
                        <div className={cn('w-10 h-10 rounded-xl flex items-center justify-center mb-3', s.color)}>
                            <s.icon className="w-5 h-5 text-white" />
                        </div>
                        <p className="text-2xl font-bold text-gray-900">{s.value}</p>
                        <p className="text-sm text-gray-500">{s.label}</p>
                    </div>
                ))}
            </div>

            {/* Table card */}
            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm">
                {/* Toolbar */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center gap-3 px-4 py-3 border-b border-gray-100">
                    <div className="relative flex-1 w-full">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input value={search} onChange={e => { setSearch(e.target.value); setPage(1); }}
                            placeholder="Search by name, phone, email..."
                            className="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500" />
                    </div>
                    <div className="flex items-center gap-2">
                        <select value={segment} onChange={e => { setSegment(e.target.value); setPage(1); }}
                            className="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="">All Segments</option>
                            {Object.entries(SEGMENTS).map(([k, s]) => (
                                <option key={k} value={k}>{s.label}</option>
                            ))}
                        </select>
                        <Button size="sm" onClick={() => setAddModal(true)}>
                            <Plus className="w-4 h-4" /> Add
                        </Button>
                    </div>
                </div>

                {/* Table */}
                {loading ? (
                    <div className="py-12 text-center text-gray-400">Loading...</div>
                ) : customers.length === 0 ? (
                    <div className="py-12 text-center">
                        <Users className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                        <p className="text-gray-500">No customers found</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="text-left text-xs text-gray-500 border-b border-gray-100">
                                    <th className="px-4 py-2.5 font-medium">Customer</th>
                                    <th className="px-4 py-2.5 font-medium">Phone</th>
                                    <th className="px-4 py-2.5 font-medium">Segment</th>
                                    <th className="px-4 py-2.5 font-medium">Orders</th>
                                    <th className="px-4 py-2.5 font-medium">Total Spent</th>
                                    <th className="px-4 py-2.5 font-medium">Points</th>
                                    <th className="px-4 py-2.5 font-medium">Last Visit</th>
                                    <th className="px-4 py-2.5 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {customers.map(c => (
                                    <tr key={c.id} className="hover:bg-gray-50/50 transition-colors">
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                <div className="w-8 h-8 rounded-full bg-gradient-to-br from-violet-400 to-indigo-500 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                                                    {c.name[0].toUpperCase()}
                                                </div>
                                                <div>
                                                    <p className="font-medium text-gray-800 text-sm">{c.name}</p>
                                                    {c.email && <p className="text-xs text-gray-400">{c.email}</p>}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-600">{c.phone}</td>
                                        <td className="px-4 py-3"><SegmentBadge segment={c.segment} /></td>
                                        <td className="px-4 py-3 text-sm text-gray-700 font-medium">{c.orders_count ?? c.total_orders ?? 0}</td>
                                        <td className="px-4 py-3 text-sm font-semibold text-gray-800">{formatCurrency(c.total_spent ?? 0)}</td>
                                        <td className="px-4 py-3">
                                            <span className="flex items-center gap-1 text-sm text-amber-600">
                                                <Star className="w-3.5 h-3.5 fill-current" />
                                                {c.loyalty_points ?? 0}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-xs text-gray-400">{c.last_visit_at ? formatDate(c.last_visit_at) : '—'}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center justify-end gap-1">
                                                <button onClick={() => setViewItem(c)}
                                                    className="p-1.5 text-gray-400 hover:text-violet-600 hover:bg-violet-50 rounded-lg transition-colors">
                                                    <Eye className="w-4 h-4" />
                                                </button>
                                                <button onClick={() => setEditItem(c)}
                                                    className="p-1.5 text-gray-400 hover:text-violet-600 hover:bg-violet-50 rounded-lg transition-colors">
                                                    <Edit2 className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Pagination */}
                {meta.last_page > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t border-gray-100">
                        <p className="text-sm text-gray-500">
                            {meta.from}–{meta.to} of {meta.total} customers
                        </p>
                        <div className="flex gap-2">
                            <Button size="sm" variant="outline" disabled={page === 1} onClick={() => setPage(p => p - 1)}>Previous</Button>
                            <Button size="sm" variant="outline" disabled={page === meta.last_page} onClick={() => setPage(p => p + 1)}>Next</Button>
                        </div>
                    </div>
                )}
            </div>

            {/* Modals */}
            {addModal && <CustomerModal onClose={() => setAddModal(false)} onSaved={() => { setAddModal(false); fetch(); }} />}
            {editItem && <CustomerModal customer={editItem} onClose={() => setEditItem(null)} onSaved={() => { setEditItem(null); fetch(); }} />}
            {viewItem && <CustomerProfile customer={viewItem} onClose={() => setViewItem(null)} />}
        </AppLayout>
    );
}
