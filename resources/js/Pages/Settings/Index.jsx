import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { router } from '@inertiajs/react';
import axios from 'axios';
import {
    Settings, Building2, Receipt, Percent, Globe,
    Bell, Shield, CreditCard, Plus, Save, Edit2,
    Check, X, ToggleLeft, ToggleRight, ChevronRight,
    Printer, Smartphone,
} from 'lucide-react';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import { cn, formatCurrency } from '@/lib/utils';

// ── Setting Group ──────────────────────────────────────────────────────────
function SettingGroup({ title, icon: Icon, children }) {
    return (
        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <div className="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100">
                <div className="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">
                    <Icon className="w-4 h-4 text-violet-600" />
                </div>
                <h3 className="font-semibold text-gray-800">{title}</h3>
            </div>
            {children}
        </div>
    );
}

// ── Setting Row ────────────────────────────────────────────────────────────
function SettingRow({ label, description, children }) {
    return (
        <div className="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
            <div>
                <p className="text-sm font-medium text-gray-700">{label}</p>
                {description && <p className="text-xs text-gray-400 mt-0.5">{description}</p>}
            </div>
            <div className="ml-4 flex-shrink-0">{children}</div>
        </div>
    );
}

// ── Toggle ─────────────────────────────────────────────────────────────────
function Toggle({ checked, onChange }) {
    return (
        <button onClick={() => onChange(!checked)}
            className={cn('transition-colors', checked ? 'text-violet-600' : 'text-gray-300')}>
            {checked ? <ToggleRight className="w-8 h-8" /> : <ToggleLeft className="w-8 h-8" />}
        </button>
    );
}

// ── Tax Rate Modal ─────────────────────────────────────────────────────────
function TaxModal({ tax, onClose, onSaved }) {
    const isEdit = !!tax?.id;
    const [form, setForm] = useState({
        name:         tax?.name || '',
        rate:         tax?.rate || '',
        type:         tax?.type || 'vat',
        is_inclusive: tax?.is_inclusive ?? false,
        is_default:   tax?.is_default   ?? false,
    });
    const [saving, setSaving] = useState(false);
    const set = (k, v) => setForm(p => ({ ...p, [k]: v }));

    const submit = async () => {
        setSaving(true);
        try {
            if (isEdit) await axios.put(`/api/settings/tax-rates/${tax.id}`, form);
            else         await axios.post('/api/settings/tax-rates', form);
            onSaved();
        } finally { setSaving(false); }
    };

    return (
        <Modal open onClose={onClose} title={isEdit ? 'Edit Tax Rate' : 'Add Tax Rate'}>
            <div className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <Input value={form.name} onChange={e => set('name', e.target.value)} placeholder="e.g. VAT 15%" />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Rate (%) *</label>
                        <Input type="number" min={0} max={100} step="0.01" value={form.rate}
                            onChange={e => set('rate', parseFloat(e.target.value))} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select value={form.type} onChange={e => set('type', e.target.value)}
                            className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="vat">VAT</option>
                            <option value="service_charge">Service Charge</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div className="space-y-2">
                    <label className="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" checked={form.is_inclusive}
                            onChange={e => set('is_inclusive', e.target.checked)}
                            className="w-4 h-4 text-violet-600 rounded" />
                        <span className="text-sm text-gray-700">Tax inclusive (included in price)</span>
                    </label>
                    <label className="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" checked={form.is_default}
                            onChange={e => set('is_default', e.target.checked)}
                            className="w-4 h-4 text-violet-600 rounded" />
                        <span className="text-sm text-gray-700">Set as default rate</span>
                    </label>
                </div>
                <div className="flex gap-3 pt-2">
                    <Button variant="outline" className="flex-1" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>
                        <Save className="w-4 h-4" /> {isEdit ? 'Update' : 'Add Rate'}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Branch Modal ───────────────────────────────────────────────────────────
function BranchModal({ onClose, onSaved }) {
    const [form, setForm] = useState({ name: '', phone: '', address: '', city: 'Dhaka' });
    const [saving, setSaving] = useState(false);
    const set = (k, v) => setForm(p => ({ ...p, [k]: v }));

    const submit = async () => {
        setSaving(true);
        try {
            await axios.post('/api/settings/branches', form);
            onSaved();
        } finally { setSaving(false); }
    };

    return (
        <Modal open onClose={onClose} title="Add Branch">
            <div className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Branch Name *</label>
                    <Input value={form.name} onChange={e => set('name', e.target.value)} placeholder="e.g. Gulshan Branch" />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <Input value={form.phone} onChange={e => set('phone', e.target.value)} placeholder="01XXXXXXXXX" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">City</label>
                        <Input value={form.city} onChange={e => set('city', e.target.value)} />
                    </div>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <Input value={form.address} onChange={e => set('address', e.target.value)} placeholder="Full address" />
                </div>
                <div className="flex gap-3 pt-2">
                    <Button variant="outline" className="flex-1" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>
                        <Save className="w-4 h-4" /> Create Branch
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Main Page ──────────────────────────────────────────────────────────────
export default function SettingsIndex({ settings = {}, taxRates = [], branches = [] }) {
    const [tab, setTab]             = useState('general');
    const [form, setForm]           = useState({
        restaurant_name:    settings.restaurant_name    || '',
        currency_symbol:    settings.currency_symbol    || '৳',
        timezone:           settings.timezone           || 'Asia/Dhaka',
        receipt_footer:     settings.receipt_footer     || 'Thank you for dining with us!',
        enable_qr_order:    settings.enable_qr_order    === 'true',
        enable_kitchen_display: settings.enable_kitchen_display !== 'false',
        auto_print_receipt: settings.auto_print_receipt === 'true',
        service_charge:     settings.service_charge     || '0',
    });
    const [saving, setSaving]       = useState(false);
    const [taxModal, setTaxModal]   = useState(false);
    const [editTax, setEditTax]     = useState(null);
    const [branchModal, setBranchModal] = useState(false);
    const [localTaxes, setLocalTaxes]  = useState(taxRates);
    const [localBranches, setLocalBranches] = useState(branches);
    const set = (k, v) => setForm(p => ({ ...p, [k]: v }));

    const saveGeneral = async () => {
        setSaving(true);
        try {
            const payload = { ...form, enable_qr_order: form.enable_qr_order ? 'true' : 'false',
                enable_kitchen_display: form.enable_kitchen_display ? 'true' : 'false',
                auto_print_receipt: form.auto_print_receipt ? 'true' : 'false' };
            await axios.put('/api/settings', { settings: payload });
        } finally { setSaving(false); }
    };

    const TABS = [
        { key: 'general',  label: 'General',    icon: Settings },
        { key: 'tax',      label: 'Tax & VAT',  icon: Percent },
        { key: 'branches', label: 'Branches',   icon: Building2 },
        { key: 'pos',      label: 'POS',        icon: CreditCard },
        { key: 'receipt',  label: 'Receipt',    icon: Receipt },
    ];

    return (
        <AppLayout title="Settings">
            <div className="flex gap-6">
                {/* Sidebar nav */}
                <div className="w-48 flex-shrink-0">
                    <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-2 space-y-0.5">
                        {TABS.map(t => (
                            <button key={t.key} onClick={() => setTab(t.key)}
                                className={cn(
                                    'w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                                    tab === t.key
                                        ? 'bg-violet-50 text-violet-700'
                                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
                                )}>
                                <t.icon className="w-4 h-4" />
                                {t.label}
                                {tab === t.key && <ChevronRight className="w-3.5 h-3.5 ml-auto" />}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Content */}
                <div className="flex-1 space-y-5">
                    {/* General */}
                    {tab === 'general' && (
                        <SettingGroup title="General Settings" icon={Settings}>
                            <div className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Restaurant Name</label>
                                        <Input value={form.restaurant_name}
                                            onChange={e => set('restaurant_name', e.target.value)} />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Currency Symbol</label>
                                        <Input value={form.currency_symbol}
                                            onChange={e => set('currency_symbol', e.target.value)} />
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                                    <select value={form.timezone} onChange={e => set('timezone', e.target.value)}
                                        className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                        <option value="Asia/Dhaka">Asia/Dhaka (BST +6:00)</option>
                                        <option value="UTC">UTC</option>
                                    </select>
                                </div>

                                <SettingRow label="QR Code Ordering" description="Allow customers to order via QR code">
                                    <Toggle checked={form.enable_qr_order} onChange={v => set('enable_qr_order', v)} />
                                </SettingRow>
                                <SettingRow label="Kitchen Display System" description="Show KDS for kitchen staff">
                                    <Toggle checked={form.enable_kitchen_display} onChange={v => set('enable_kitchen_display', v)} />
                                </SettingRow>
                                <SettingRow label="Auto-print Receipt" description="Print receipt automatically on payment">
                                    <Toggle checked={form.auto_print_receipt} onChange={v => set('auto_print_receipt', v)} />
                                </SettingRow>

                                <Button onClick={saveGeneral} loading={saving}>
                                    <Save className="w-4 h-4" /> Save Changes
                                </Button>
                            </div>
                        </SettingGroup>
                    )}

                    {/* Tax & VAT */}
                    {tab === 'tax' && (
                        <SettingGroup title="Tax & VAT Configuration" icon={Percent}>
                            <div className="flex items-center justify-between mb-4">
                                <p className="text-sm text-gray-600">Configure Bangladesh VAT rates (NBR compliant)</p>
                                <Button size="sm" onClick={() => setTaxModal(true)}>
                                    <Plus className="w-4 h-4" /> Add Rate
                                </Button>
                            </div>

                            {localTaxes.length === 0 ? (
                                <div className="py-8 text-center text-gray-400">
                                    <Percent className="w-10 h-10 mx-auto mb-2 text-gray-200" />
                                    No tax rates configured
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    {localTaxes.map(tax => (
                                        <div key={tax.id}
                                            className={cn(
                                                'flex items-center justify-between p-4 border rounded-xl transition-colors',
                                                tax.is_active ? 'border-gray-200 bg-white' : 'border-gray-100 bg-gray-50 opacity-70'
                                            )}>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <p className="font-semibold text-gray-800">{tax.name}</p>
                                                    {tax.is_default && (
                                                        <span className="text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">Default</span>
                                                    )}
                                                    {tax.is_inclusive && (
                                                        <span className="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Inclusive</span>
                                                    )}
                                                </div>
                                                <p className="text-sm text-gray-500 capitalize">{tax.type} · {tax.rate}%</p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <button onClick={() => setEditTax(tax)}
                                                    className="p-1.5 text-gray-400 hover:text-violet-600 hover:bg-violet-50 rounded-lg transition-colors">
                                                    <Edit2 className="w-4 h-4" />
                                                </button>
                                                <div className="text-2xl font-bold text-gray-900">{tax.rate}%</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            <div className="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700">
                                Bangladesh VAT standard rate: <strong>15%</strong>. Reduced rates: 5%, 7.5%, 10% for specific categories.
                                Refer to NBR circular for applicable rates.
                            </div>
                        </SettingGroup>
                    )}

                    {/* Branches */}
                    {tab === 'branches' && (
                        <SettingGroup title="Branch Management" icon={Building2}>
                            <div className="flex items-center justify-between mb-4">
                                <p className="text-sm text-gray-600">Manage all restaurant branches</p>
                                <Button size="sm" onClick={() => setBranchModal(true)}>
                                    <Plus className="w-4 h-4" /> Add Branch
                                </Button>
                            </div>

                            <div className="space-y-3">
                                {localBranches.map(branch => (
                                    <div key={branch.id}
                                        className="flex items-center justify-between p-4 border border-gray-200 rounded-xl hover:border-violet-200 transition-colors">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center">
                                                <Building2 className="w-5 h-5 text-violet-600" />
                                            </div>
                                            <div>
                                                <p className="font-semibold text-gray-800">{branch.name}</p>
                                                <p className="text-xs text-gray-500">{branch.address || 'No address'}</p>
                                            </div>
                                        </div>
                                        <span className={cn(
                                            'text-xs px-2.5 py-1 rounded-full font-medium',
                                            branch.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'
                                        )}>
                                            {branch.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </SettingGroup>
                    )}

                    {/* POS */}
                    {tab === 'pos' && (
                        <SettingGroup title="POS Settings" icon={CreditCard}>
                            <div className="space-y-0">
                                <SettingRow label="Service Charge (%)" description="Additional service charge on orders">
                                    <Input type="number" min={0} max={100} step="0.5"
                                        value={form.service_charge}
                                        onChange={e => set('service_charge', e.target.value)}
                                        className="w-24 text-right" />
                                </SettingRow>
                                <SettingRow label="Allow Split Bill" description="Enable split bill feature at POS">
                                    <Toggle checked={true} onChange={() => {}} />
                                </SettingRow>
                                <SettingRow label="bKash / Nagad" description="Accept mobile payment methods">
                                    <Toggle checked={true} onChange={() => {}} />
                                </SettingRow>
                                <SettingRow label="Loyalty Points at POS" description="Allow loyalty point redemption">
                                    <Toggle checked={false} onChange={() => {}} />
                                </SettingRow>
                            </div>
                            <div className="mt-4">
                                <Button onClick={saveGeneral} loading={saving}>
                                    <Save className="w-4 h-4" /> Save POS Settings
                                </Button>
                            </div>
                        </SettingGroup>
                    )}

                    {/* Receipt */}
                    {tab === 'receipt' && (
                        <SettingGroup title="Receipt Settings" icon={Receipt}>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Receipt Footer Message</label>
                                    <textarea value={form.receipt_footer}
                                        onChange={e => set('receipt_footer', e.target.value)}
                                        rows={3}
                                        className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 resize-none"
                                        placeholder="Thank you message on receipt..." />
                                </div>
                                <SettingRow label="Auto Print" description="Automatically print after payment">
                                    <Toggle checked={form.auto_print_receipt} onChange={v => set('auto_print_receipt', v)} />
                                </SettingRow>

                                {/* Receipt preview */}
                                <div className="border-2 border-dashed border-gray-200 rounded-xl p-5">
                                    <div className="max-w-xs mx-auto font-mono text-xs text-center">
                                        <p className="font-bold text-sm mb-1">{form.restaurant_name || 'Restaurant Name'}</p>
                                        <p className="text-gray-500">Invoice No: ORD-001</p>
                                        <p className="text-gray-500">{new Date().toLocaleDateString('en-BD')}</p>
                                        <div className="border-t border-dashed border-gray-300 my-2" />
                                        <div className="flex justify-between">
                                            <span>Chicken Biryani x1</span>
                                            <span>৳280</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Drinks x2</span>
                                            <span>৳80</span>
                                        </div>
                                        <div className="border-t border-dashed border-gray-300 my-2" />
                                        <div className="flex justify-between font-bold">
                                            <span>VAT (15%)</span>
                                            <span>৳54</span>
                                        </div>
                                        <div className="flex justify-between font-bold text-sm">
                                            <span>TOTAL</span>
                                            <span>৳414</span>
                                        </div>
                                        <div className="border-t border-dashed border-gray-300 my-2" />
                                        <p className="text-gray-400 text-xs">{form.receipt_footer}</p>
                                    </div>
                                </div>

                                <Button onClick={saveGeneral} loading={saving}>
                                    <Save className="w-4 h-4" /> Save Receipt Settings
                                </Button>
                            </div>
                        </SettingGroup>
                    )}
                </div>
            </div>

            {/* Modals */}
            {taxModal && (
                <TaxModal
                    onClose={() => setTaxModal(false)}
                    onSaved={() => { setTaxModal(false); router.reload({ only: ['taxRates'] }); }}
                />
            )}
            {editTax && (
                <TaxModal
                    tax={editTax}
                    onClose={() => setEditTax(null)}
                    onSaved={() => { setEditTax(null); router.reload({ only: ['taxRates'] }); }}
                />
            )}
            {branchModal && (
                <BranchModal
                    onClose={() => setBranchModal(false)}
                    onSaved={() => { setBranchModal(false); router.reload({ only: ['branches'] }); }}
                />
            )}
        </AppLayout>
    );
}
