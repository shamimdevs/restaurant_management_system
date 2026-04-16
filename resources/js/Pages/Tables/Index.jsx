import { useState, useCallback } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { router } from '@inertiajs/react';
import axios from 'axios';
import {
    Plus, QrCode, RefreshCw, Users, Clock,
    CheckCircle, Coffee, Wrench, ChevronDown,
    Edit2, Trash2, Download, X, Save,
} from 'lucide-react';
import Button from '@/Components/UI/Button';
import Modal from '@/Components/UI/Modal';
import Input from '@/Components/UI/Input';
import { cn, formatTime, formatCurrency } from '@/lib/utils';

// ── Status config ──────────────────────────────────────────────────────────
const STATUS = {
    available:   { label: 'Available',   color: 'bg-emerald-100 text-emerald-700 border-emerald-200', dot: 'bg-emerald-400', icon: CheckCircle },
    occupied:    { label: 'Occupied',    color: 'bg-violet-100  text-violet-700  border-violet-200',  dot: 'bg-violet-400',  icon: Users },
    reserved:    { label: 'Reserved',    color: 'bg-amber-100   text-amber-700   border-amber-200',   dot: 'bg-amber-400',   icon: Clock },
    maintenance: { label: 'Maintenance', color: 'bg-red-100     text-red-700     border-red-200',     dot: 'bg-red-400',     icon: Wrench },
};

// ── Table Card ─────────────────────────────────────────────────────────────
function TableCard({ table, onQr, onEdit, onStatus }) {
    const cfg   = STATUS[table.status] ?? STATUS.available;
    const Icon  = cfg.icon;
    const shape = table.shape === 'round' ? 'rounded-full' : table.shape === 'rectangle' ? 'rounded-lg' : 'rounded-xl';

    return (
        <div className={cn(
            'group relative bg-white border-2 rounded-2xl p-4 shadow-sm hover:shadow-md transition-all cursor-pointer',
            table.status === 'occupied' ? 'border-violet-200' :
            table.status === 'reserved' ? 'border-amber-200' :
            table.status === 'maintenance' ? 'border-red-200' : 'border-gray-200 hover:border-violet-200',
        )}>
            {/* Status badge */}
            <div className={cn('inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border mb-3', cfg.color)}>
                <span className={cn('w-1.5 h-1.5 rounded-full', cfg.dot)} />
                {cfg.label}
            </div>

            {/* Table visual */}
            <div className="flex items-center justify-center my-2">
                <div className={cn(
                    'w-16 h-16 border-4 flex items-center justify-center',
                    shape,
                    table.status === 'occupied' ? 'border-violet-400 bg-violet-50' :
                    table.status === 'reserved' ? 'border-amber-400 bg-amber-50' :
                    table.status === 'maintenance' ? 'border-red-400 bg-red-50' :
                    'border-gray-300 bg-gray-50'
                )}>
                    <span className="text-xl font-bold text-gray-700">{table.table_number}</span>
                </div>
            </div>

            {/* Info */}
            <div className="text-center">
                <p className="font-semibold text-gray-900 text-sm">{table.name || `Table ${table.table_number}`}</p>
                <p className="text-xs text-gray-500 flex items-center justify-center gap-1 mt-0.5">
                    <Users className="w-3 h-3" /> {table.capacity} seats
                </p>
            </div>

            {/* Active session info */}
            {table.active_session && (
                <div className="mt-3 pt-3 border-t border-gray-100 text-center">
                    <p className="text-xs text-gray-500">
                        Since {formatTime(table.active_session.opened_at)}
                    </p>
                    {table.active_session.current_order_total > 0 && (
                        <p className="text-xs font-semibold text-violet-600 mt-0.5">
                            {formatCurrency(table.active_session.current_order_total)}
                        </p>
                    )}
                </div>
            )}

            {/* Hover actions */}
            <div className="absolute inset-x-0 bottom-0 bg-white border-t border-gray-100 rounded-b-2xl px-3 py-2 flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                <button onClick={() => onQr(table)} className="flex-1 flex items-center justify-center gap-1 text-xs text-gray-600 hover:text-violet-600 hover:bg-violet-50 py-1.5 rounded-lg transition-colors">
                    <QrCode className="w-3.5 h-3.5" /> QR
                </button>
                <button onClick={() => onEdit(table)} className="flex-1 flex items-center justify-center gap-1 text-xs text-gray-600 hover:text-violet-600 hover:bg-violet-50 py-1.5 rounded-lg transition-colors">
                    <Edit2 className="w-3.5 h-3.5" /> Edit
                </button>
                <button onClick={() => onStatus(table)} className="flex-1 flex items-center justify-center gap-1 text-xs text-gray-600 hover:text-violet-600 hover:bg-violet-50 py-1.5 rounded-lg transition-colors">
                    <RefreshCw className="w-3.5 h-3.5" /> Status
                </button>
            </div>
        </div>
    );
}

// ── QR Modal ───────────────────────────────────────────────────────────────
function QrModal({ table, onClose }) {
    const qrUrl = table ? `${window.location.origin}/order/${table.qr_code}` : '';

    const downloadQr = () => {
        const link = document.createElement('a');
        link.href = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodeURIComponent(qrUrl)}`;
        link.download = `table-${table.table_number}-qr.png`;
        link.click();
    };

    const regenerate = async () => {
        if (!confirm('Regenerate QR code? The old one will stop working.')) return;
        try {
            await axios.post(`/api/tables/${table.id}/regenerate-qr`);
            router.reload();
            onClose();
        } catch { alert('Failed to regenerate QR'); }
    };

    return (
        <Modal open={!!table} onClose={onClose} title={`QR Code — ${table?.name || 'Table ' + table?.table_number}`}>
            {table && (
                <div className="flex flex-col items-center gap-5 py-4">
                    <div className="p-3 bg-white border-2 border-gray-200 rounded-2xl shadow-sm">
                        <img
                            src={`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrUrl)}`}
                            alt="QR Code"
                            className="w-48 h-48"
                        />
                    </div>
                    <div className="text-center">
                        <p className="text-sm font-medium text-gray-700">Scan to order</p>
                        <p className="text-xs text-gray-500 mt-1 break-all max-w-xs">{qrUrl}</p>
                    </div>
                    <div className="flex gap-3 w-full">
                        <Button variant="outline" className="flex-1" onClick={regenerate}>
                            <RefreshCw className="w-4 h-4" /> Regenerate
                        </Button>
                        <Button className="flex-1" onClick={downloadQr}>
                            <Download className="w-4 h-4" /> Download
                        </Button>
                    </div>
                </div>
            )}
        </Modal>
    );
}

// ── Add / Edit Table Modal ─────────────────────────────────────────────────
function TableFormModal({ table, floorPlans, onClose, onSaved }) {
    const isEdit = !!table?.id;
    const [form, setForm] = useState({
        table_number: table?.table_number || '',
        name:         table?.name || '',
        capacity:     table?.capacity || 4,
        shape:        table?.shape || 'square',
        floor_plan_id: table?.floor_plan_id || (floorPlans?.[0]?.id ?? ''),
    });
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});

    const set = (k, v) => setForm(p => ({ ...p, [k]: v }));

    const submit = async () => {
        setSaving(true);
        setErrors({});
        try {
            if (isEdit) {
                await axios.put(`/api/tables/${table.id}`, form);
            } else {
                await axios.post('/api/tables', form);
            }
            onSaved();
        } catch (e) {
            setErrors(e.response?.data?.errors || {});
        } finally {
            setSaving(false);
        }
    };

    return (
        <Modal open onClose={onClose} title={isEdit ? 'Edit Table' : 'Add New Table'}>
            <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Table Number *</label>
                        <Input value={form.table_number} onChange={e => set('table_number', e.target.value)}
                            placeholder="T01" error={errors.table_number?.[0]} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Name (optional)</label>
                        <Input value={form.name} onChange={e => set('name', e.target.value)} placeholder="Window Table" />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Capacity *</label>
                        <Input type="number" min={1} max={50} value={form.capacity}
                            onChange={e => set('capacity', parseInt(e.target.value))} error={errors.capacity?.[0]} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Shape</label>
                        <select value={form.shape} onChange={e => set('shape', e.target.value)}
                            className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="square">Square</option>
                            <option value="round">Round</option>
                            <option value="rectangle">Rectangle</option>
                        </select>
                    </div>
                </div>

                {floorPlans?.length > 0 && (
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Floor / Zone</label>
                        <select value={form.floor_plan_id} onChange={e => set('floor_plan_id', e.target.value)}
                            className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="">No Zone</option>
                            {floorPlans.map(f => (
                                <option key={f.id} value={f.id}>{f.name}</option>
                            ))}
                        </select>
                    </div>
                )}

                <div className="flex gap-3 pt-2">
                    <Button variant="outline" className="flex-1" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>
                        <Save className="w-4 h-4" /> {isEdit ? 'Update' : 'Create Table'}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Status Change Modal ────────────────────────────────────────────────────
function StatusModal({ table, onClose, onSaved }) {
    const [status, setStatus] = useState(table?.status || 'available');
    const [saving, setSaving] = useState(false);

    const submit = async () => {
        setSaving(true);
        try {
            await axios.put(`/api/tables/${table.id}`, { status });
            onSaved();
        } catch { alert('Failed'); }
        finally { setSaving(false); }
    };

    return (
        <Modal open onClose={onClose} title={`Change Status — Table ${table?.table_number}`}>
            <div className="space-y-3">
                {Object.entries(STATUS).map(([key, cfg]) => (
                    <button key={key} onClick={() => setStatus(key)}
                        className={cn(
                            'w-full flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all text-left',
                            status === key ? 'border-violet-500 bg-violet-50' : 'border-gray-200 hover:border-gray-300'
                        )}>
                        <span className={cn('w-3 h-3 rounded-full', cfg.dot)} />
                        <span className="font-medium text-gray-800">{cfg.label}</span>
                        {status === key && <CheckCircle className="ml-auto w-4 h-4 text-violet-600" />}
                    </button>
                ))}
                <div className="flex gap-3 pt-2">
                    <Button variant="outline" className="flex-1" onClick={onClose}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>Save Status</Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Main Page ──────────────────────────────────────────────────────────────
export default function TablesIndex({ floorPlans = [] }) {
    const [activeFloor, setActiveFloor] = useState(floorPlans[0]?.id ?? null);
    const [qrTable,     setQrTable]     = useState(null);
    const [editTable,   setEditTable]   = useState(null);
    const [statusTable, setStatusTable] = useState(null);
    const [showAdd,     setShowAdd]     = useState(false);

    const allTables = floorPlans.flatMap(f => f.tables || []);
    const currentFloor = floorPlans.find(f => f.id === activeFloor);
    const displayTables = activeFloor ? (currentFloor?.tables || []) : allTables;

    const reload = () => router.reload({ only: ['floorPlans'] });

    // Stats
    const stats = {
        total:       allTables.length,
        available:   allTables.filter(t => t.status === 'available').length,
        occupied:    allTables.filter(t => t.status === 'occupied').length,
        reserved:    allTables.filter(t => t.status === 'reserved').length,
    };

    return (
        <AppLayout title="Tables & Floor Plan">
            {/* ── Summary strip ── */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                {[
                    { label: 'Total',     value: stats.total,     color: 'text-gray-700',    bg: 'bg-gray-50' },
                    { label: 'Available', value: stats.available, color: 'text-emerald-700', bg: 'bg-emerald-50' },
                    { label: 'Occupied',  value: stats.occupied,  color: 'text-violet-700',  bg: 'bg-violet-50' },
                    { label: 'Reserved',  value: stats.reserved,  color: 'text-amber-700',   bg: 'bg-amber-50' },
                ].map(s => (
                    <div key={s.label} className={cn('rounded-2xl p-4 border border-gray-200 shadow-sm', s.bg)}>
                        <p className="text-xs text-gray-500 mb-1">{s.label}</p>
                        <p className={cn('text-3xl font-bold', s.color)}>{s.value}</p>
                    </div>
                ))}
            </div>

            {/* ── Floor tabs + Add ── */}
            <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-2 flex-wrap">
                    <button
                        onClick={() => setActiveFloor(null)}
                        className={cn('px-4 py-2 rounded-xl text-sm font-medium transition-colors',
                            activeFloor === null ? 'bg-violet-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-violet-300'
                        )}>
                        All Tables
                    </button>
                    {floorPlans.map(f => (
                        <button key={f.id} onClick={() => setActiveFloor(f.id)}
                            className={cn('px-4 py-2 rounded-xl text-sm font-medium transition-colors',
                                activeFloor === f.id ? 'bg-violet-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-violet-300'
                            )}>
                            {f.name}
                            <span className="ml-1.5 text-xs opacity-70">({f.tables?.length || 0})</span>
                        </button>
                    ))}
                </div>
                <Button onClick={() => setShowAdd(true)}>
                    <Plus className="w-4 h-4" /> Add Table
                </Button>
            </div>

            {/* ── Table grid ── */}
            {displayTables.length === 0 ? (
                <div className="bg-white rounded-2xl border border-gray-200 p-12 text-center">
                    <Coffee className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p className="text-gray-500 font-medium">No tables yet</p>
                    <p className="text-gray-400 text-sm mt-1">Add your first table to get started</p>
                    <Button className="mt-4" onClick={() => setShowAdd(true)}>
                        <Plus className="w-4 h-4" /> Add Table
                    </Button>
                </div>
            ) : (
                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
                    {displayTables.map(table => (
                        <TableCard
                            key={table.id}
                            table={table}
                            onQr={setQrTable}
                            onEdit={setEditTable}
                            onStatus={setStatusTable}
                        />
                    ))}
                </div>
            )}

            {/* ── Modals ── */}
            {qrTable && <QrModal table={qrTable} onClose={() => setQrTable(null)} />}

            {showAdd && (
                <TableFormModal
                    floorPlans={floorPlans}
                    onClose={() => setShowAdd(false)}
                    onSaved={() => { setShowAdd(false); reload(); }}
                />
            )}
            {editTable && (
                <TableFormModal
                    table={editTable}
                    floorPlans={floorPlans}
                    onClose={() => setEditTable(null)}
                    onSaved={() => { setEditTable(null); reload(); }}
                />
            )}
            {statusTable && (
                <StatusModal
                    table={statusTable}
                    onClose={() => setStatusTable(null)}
                    onSaved={() => { setStatusTable(null); reload(); }}
                />
            )}
        </AppLayout>
    );
}
