import { useState, useEffect, useCallback } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import axios from 'axios';
import {
    UserCheck, Plus, Search, Clock, DollarSign,
    Calendar, Shield, Edit2, Eye, Save, ChevronDown,
    Check, X, AlertCircle, Briefcase, Phone,
} from 'lucide-react';
import Button from '@/Components/UI/Button';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import { cn, formatCurrency, formatDate } from '@/lib/utils';

// ── Role badge ─────────────────────────────────────────────────────────────
const ROLE_COLORS = {
    Admin:   'bg-violet-100 text-violet-700',
    Manager: 'bg-blue-100 text-blue-700',
    Cashier: 'bg-cyan-100 text-cyan-700',
    Waiter:  'bg-emerald-100 text-emerald-700',
    Kitchen: 'bg-orange-100 text-orange-700',
    Rider:   'bg-pink-100 text-pink-700',
};

function RoleBadge({ role }) {
    const cls = ROLE_COLORS[role] ?? 'bg-gray-100 text-gray-600';
    return <span className={cn('text-xs px-2 py-0.5 rounded-full font-medium', cls)}>{role || 'Staff'}</span>;
}

// ── Employee Form Modal ────────────────────────────────────────────────────
function EmployeeModal({ employee, onClose, onSaved }) {
    const isEdit = !!employee?.id;
    const [form, setForm] = useState({
        name:           employee?.name || '',
        phone:          employee?.phone || '',
        email:          employee?.email || '',
        nid:            employee?.nid || '',
        role:           employee?.role || 'Waiter',
        joining_date:   employee?.joining_date || new Date().toISOString().split('T')[0],
        basic_salary:   employee?.basic_salary || '',
        address:        employee?.address || '',
    });
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm(p => ({ ...p, [k]: v }));

    const submit = async () => {
        setSaving(true); setErrors({});
        try {
            if (isEdit) await axios.put(`/api/employees/${employee.id}`, form);
            else         await axios.post('/api/employees', form);
            onSaved();
        } catch (e) { setErrors(e.response?.data?.errors || {}); }
        finally { setSaving(false); }
    };

    return (
        <Modal open onClose={onClose} title={isEdit ? 'Edit Employee' : 'Add Employee'}>
            <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <Input value={form.name} onChange={e => set('name', e.target.value)} error={errors.name?.[0]} placeholder="Employee name" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                        <select value={form.role} onChange={e => set('role', e.target.value)}
                            className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            {Object.keys(ROLE_COLORS).map(r => <option key={r} value={r}>{r}</option>)}
                        </select>
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                        <Input value={form.phone} onChange={e => set('phone', e.target.value)} error={errors.phone?.[0]} placeholder="01XXXXXXXXX" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <Input type="email" value={form.email} onChange={e => set('email', e.target.value)} placeholder="email@example.com" />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">NID Number</label>
                        <Input value={form.nid} onChange={e => set('nid', e.target.value)} placeholder="National ID" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Joining Date</label>
                        <Input type="date" value={form.joining_date} onChange={e => set('joining_date', e.target.value)} />
                    </div>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Basic Salary (৳)</label>
                    <Input type="number" min={0} value={form.basic_salary}
                        onChange={e => set('basic_salary', e.target.value)} placeholder="Monthly basic salary" />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <Input value={form.address} onChange={e => set('address', e.target.value)} placeholder="Home address" />
                </div>
                <div className="flex gap-3 pt-2">
                    <Button variant="outline" className="flex-1" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>
                        <Save className="w-4 h-4" /> {isEdit ? 'Update' : 'Add Employee'}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Attendance Modal ───────────────────────────────────────────────────────
function AttendanceModal({ onClose }) {
    const [form, setForm] = useState({ employee_id: '', type: 'check_in', time: new Date().toTimeString().slice(0,5), notes: '' });
    const [employees, setEmployees] = useState([]);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        axios.get('/api/employees').then(r => setEmployees(r.data.data || r.data));
    }, []);

    const submit = async () => {
        setSaving(true);
        try {
            await axios.post('/api/employees/attendance', form);
            onClose();
        } catch (e) { alert(e.response?.data?.message || 'Failed'); }
        finally { setSaving(false); }
    };

    return (
        <Modal open onClose={onClose} title="Mark Attendance">
            <div className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Employee *</label>
                    <select value={form.employee_id} onChange={e => setForm(p => ({ ...p, employee_id: e.target.value }))}
                        className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">Select employee</option>
                        {employees.map(emp => <option key={emp.id} value={emp.id}>{emp.name}</option>)}
                    </select>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select value={form.type} onChange={e => setForm(p => ({ ...p, type: e.target.value }))}
                            className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="check_in">Check In</option>
                            <option value="check_out">Check Out</option>
                            <option value="break_start">Break Start</option>
                            <option value="break_end">Break End</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Time</label>
                        <Input type="time" value={form.time} onChange={e => setForm(p => ({ ...p, time: e.target.value }))} />
                    </div>
                </div>
                <div className="flex gap-3 pt-2">
                    <Button variant="outline" className="flex-1" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>
                        <Clock className="w-4 h-4" /> Mark Attendance
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Main Page ──────────────────────────────────────────────────────────────
export default function EmployeesIndex({ employees: initialEmployees = [] }) {
    const [employees, setEmployees]  = useState(initialEmployees);
    const [search, setSearch]        = useState('');
    const [roleFilter, setRoleFilter] = useState('');
    const [tab, setTab]              = useState('list'); // list | attendance | payroll
    const [addModal, setAddModal]    = useState(false);
    const [editItem, setEditItem]    = useState(null);
    const [attendanceModal, setAttendanceModal] = useState(false);

    const filtered = employees.filter(e => {
        const matchSearch = !search || e.name.toLowerCase().includes(search.toLowerCase()) || e.phone?.includes(search);
        const matchRole   = !roleFilter || e.role === roleFilter;
        return matchSearch && matchRole;
    });

    const reload = async () => {
        const res = await axios.get('/api/employees');
        setEmployees(res.data.data || res.data);
    };

    useEffect(() => { if (initialEmployees.length === 0) reload(); }, []);

    // Stats
    const byRole = Object.keys(ROLE_COLORS).reduce((acc, r) => {
        acc[r] = employees.filter(e => e.role === r).length;
        return acc;
    }, {});

    return (
        <AppLayout title="Employee Management">
            {/* Stats */}
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                {Object.entries(byRole).map(([role, count]) => (
                    <div key={role} className="bg-white rounded-2xl border border-gray-200 p-3 shadow-sm text-center">
                        <p className="text-2xl font-bold text-gray-900">{count}</p>
                        <RoleBadge role={role} />
                    </div>
                ))}
            </div>

            {/* Main card */}
            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm">
                {/* Tabs + toolbar */}
                <div className="flex items-center justify-between px-4 pt-4 border-b border-gray-100">
                    <div className="flex gap-1">
                        {[
                            { key: 'list',       label: 'Employees' },
                            { key: 'attendance', label: 'Attendance' },
                            { key: 'payroll',    label: 'Payroll' },
                            { key: 'leave',      label: 'Leave' },
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
                    <div className="flex gap-2 pb-2">
                        {tab === 'list' && (
                            <Button size="sm" onClick={() => setAddModal(true)}>
                                <Plus className="w-4 h-4" /> Add
                            </Button>
                        )}
                        {tab === 'attendance' && (
                            <Button size="sm" onClick={() => setAttendanceModal(true)}>
                                <Clock className="w-4 h-4" /> Mark
                            </Button>
                        )}
                    </div>
                </div>

                {/* Employee list */}
                {tab === 'list' && (
                    <div className="p-4">
                        <div className="flex gap-3 mb-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input value={search} onChange={e => setSearch(e.target.value)}
                                    placeholder="Search employees..."
                                    className="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500" />
                            </div>
                            <select value={roleFilter} onChange={e => setRoleFilter(e.target.value)}
                                className="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                <option value="">All Roles</option>
                                {Object.keys(ROLE_COLORS).map(r => <option key={r} value={r}>{r}</option>)}
                            </select>
                        </div>

                        {filtered.length === 0 ? (
                            <div className="py-12 text-center">
                                <UserCheck className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                                <p className="text-gray-500">No employees found</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                {filtered.map(emp => (
                                    <div key={emp.id}
                                        className="border border-gray-200 rounded-2xl p-4 hover:border-violet-200 hover:shadow-sm transition-all">
                                        <div className="flex items-center gap-3 mb-3">
                                            <div className="w-10 h-10 rounded-full bg-gradient-to-br from-violet-400 to-indigo-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                                                {emp.name[0].toUpperCase()}
                                            </div>
                                            <div className="min-w-0">
                                                <p className="font-semibold text-gray-800 text-sm truncate">{emp.name}</p>
                                                <p className="text-xs text-gray-500">{emp.employee_id}</p>
                                            </div>
                                        </div>
                                        <RoleBadge role={emp.role} />
                                        <div className="mt-3 space-y-1.5">
                                            <p className="text-xs text-gray-500 flex items-center gap-1.5">
                                                <Phone className="w-3 h-3" /> {emp.phone}
                                            </p>
                                            <p className="text-xs text-gray-500 flex items-center gap-1.5">
                                                <Calendar className="w-3 h-3" /> Joined {formatDate(emp.joining_date)}
                                            </p>
                                            <p className="text-xs font-semibold text-violet-600 flex items-center gap-1.5">
                                                <DollarSign className="w-3 h-3" /> {formatCurrency(emp.basic_salary ?? 0)}/mo
                                            </p>
                                        </div>
                                        <div className="flex gap-2 mt-3 pt-3 border-t border-gray-100">
                                            <button onClick={() => setEditItem(emp)}
                                                className="flex-1 flex items-center justify-center gap-1 text-xs text-gray-600 hover:text-violet-600 hover:bg-violet-50 py-1.5 rounded-lg transition-colors">
                                                <Edit2 className="w-3.5 h-3.5" /> Edit
                                            </button>
                                            <span className={cn(
                                                'flex-1 flex items-center justify-center gap-1 text-xs py-1.5 rounded-lg',
                                                emp.is_active ? 'text-emerald-600 bg-emerald-50' : 'text-gray-400 bg-gray-50'
                                            )}>
                                                {emp.is_active ? <><Check className="w-3.5 h-3.5" /> Active</> : 'Inactive'}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* Attendance tab */}
                {tab === 'attendance' && (
                    <div className="p-6 text-center">
                        <Clock className="w-12 h-12 text-violet-200 mx-auto mb-3" />
                        <p className="text-gray-600 font-medium">Attendance Tracking</p>
                        <p className="text-gray-400 text-sm mt-1">Daily check-in / check-out management</p>
                        <Button className="mt-4" onClick={() => setAttendanceModal(true)}>
                            <Clock className="w-4 h-4" /> Mark Attendance
                        </Button>
                    </div>
                )}

                {/* Payroll tab */}
                {tab === 'payroll' && (
                    <div className="p-4">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="font-semibold text-gray-800">Monthly Payroll</h3>
                            <Button size="sm">
                                <DollarSign className="w-4 h-4" /> Process Payroll
                            </Button>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-left text-xs text-gray-500 border-b border-gray-100">
                                        <th className="pb-2 font-medium">Employee</th>
                                        <th className="pb-2 font-medium">Role</th>
                                        <th className="pb-2 font-medium">Basic</th>
                                        <th className="pb-2 font-medium">Allowances</th>
                                        <th className="pb-2 font-medium">Deductions</th>
                                        <th className="pb-2 font-medium">Net Pay</th>
                                        <th className="pb-2 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {employees.map(emp => (
                                        <tr key={emp.id} className="hover:bg-gray-50/50">
                                            <td className="py-3 pr-4 font-medium text-gray-800">{emp.name}</td>
                                            <td className="py-3 pr-4"><RoleBadge role={emp.role} /></td>
                                            <td className="py-3 pr-4">{formatCurrency(emp.basic_salary ?? 0)}</td>
                                            <td className="py-3 pr-4 text-emerald-600">+{formatCurrency(0)}</td>
                                            <td className="py-3 pr-4 text-red-600">-{formatCurrency(0)}</td>
                                            <td className="py-3 pr-4 font-semibold">{formatCurrency(emp.basic_salary ?? 0)}</td>
                                            <td className="py-3">
                                                <span className="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Pending</span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Leave tab */}
                {tab === 'leave' && (
                    <div className="p-6 text-center">
                        <Calendar className="w-12 h-12 text-violet-200 mx-auto mb-3" />
                        <p className="text-gray-600 font-medium">Leave Management</p>
                        <p className="text-gray-400 text-sm">Approve and track employee leave requests</p>
                    </div>
                )}
            </div>

            {/* Modals */}
            {addModal && <EmployeeModal onClose={() => setAddModal(false)} onSaved={() => { setAddModal(false); reload(); }} />}
            {editItem && <EmployeeModal employee={editItem} onClose={() => setEditItem(null)} onSaved={() => { setEditItem(null); reload(); }} />}
            {attendanceModal && <AttendanceModal onClose={() => setAttendanceModal(false)} />}
        </AppLayout>
    );
}
