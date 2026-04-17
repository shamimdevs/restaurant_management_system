import { useState, useEffect, useCallback } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import {
    UserCheck, Plus, Search, Clock, DollarSign,
    Calendar, Shield, Edit2, Eye, Save, ChevronDown,
    Check, X, AlertCircle, Briefcase, Phone,
    CheckCircle, XCircle, Filter, Building2,
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
function EmployeeModal({ employee, onClose, onSaved, isAdmin = false, branches = [] }) {
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
        branch_id:      employee?.branch_id || branches[0]?.id || '',
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
                {isAdmin && !isEdit && branches.length > 0 && (
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Branch *</label>
                        <select value={form.branch_id} onChange={e => set('branch_id', e.target.value)}
                            className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            {branches.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
                        </select>
                    </div>
                )}
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

// ── Leave Modal ────────────────────────────────────────────────────────────
function LeaveModal({ onClose, onSaved }) {
    const [employees, setEmployees]   = useState([]);
    const [leaveTypes, setLeaveTypes] = useState([]);
    const [form, setForm] = useState({
        employee_id:   '',
        leave_type_id: '',
        from_date:     new Date().toISOString().split('T')[0],
        to_date:       new Date().toISOString().split('T')[0],
        reason:        '',
    });
    const [saving, setSaving] = useState(false);
    const set = (k, v) => setForm(p => ({ ...p, [k]: v }));

    useEffect(() => {
        Promise.all([
            axios.get('/api/employees'),
            axios.get('/api/employees/leave-types'),
        ]).then(([emp, lt]) => {
            setEmployees(emp.data.data || emp.data);
            setLeaveTypes(lt.data);
        });
    }, []);

    const submit = async () => {
        setSaving(true);
        try {
            await axios.post('/api/employees/leave', form);
            onSaved();
        } catch (e) { alert(e.response?.data?.message || 'Failed'); }
        finally { setSaving(false); }
    };

    return (
        <Modal open onClose={onClose} title="Apply for Leave">
            <div className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Employee *</label>
                    <select value={form.employee_id} onChange={e => set('employee_id', e.target.value)}
                        className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">Select employee</option>
                        {employees.map(emp => <option key={emp.id} value={emp.id}>{emp.name}</option>)}
                    </select>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Leave Type *</label>
                    <select value={form.leave_type_id} onChange={e => set('leave_type_id', e.target.value)}
                        className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">Select type</option>
                        {leaveTypes.map(lt => <option key={lt.id} value={lt.id}>{lt.name} ({lt.days_allowed_per_year} days/yr)</option>)}
                    </select>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">From Date *</label>
                        <Input type="date" value={form.from_date} onChange={e => set('from_date', e.target.value)} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">To Date *</label>
                        <Input type="date" value={form.to_date} onChange={e => set('to_date', e.target.value)} />
                    </div>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <textarea value={form.reason} onChange={e => set('reason', e.target.value)} rows={2}
                        className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 resize-none"
                        placeholder="Reason for leave..." />
                </div>
                <div className="flex gap-3 pt-2">
                    <Button variant="outline" className="flex-1" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button className="flex-1" onClick={submit} loading={saving}>
                        <Save className="w-4 h-4" /> Submit Leave
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

// ── Main Page ──────────────────────────────────────────────────────────────
export default function EmployeesIndex({ employees: initialEmployees = [] }) {
    const { auth } = usePage().props;
    const isAdmin = !auth?.user?.branch_id;

    const [employees, setEmployees]  = useState(initialEmployees);
    const [branches, setBranches]    = useState([]);
    const [search, setSearch]        = useState('');
    const [roleFilter, setRoleFilter] = useState('');
    const [branchFilter, setBranchFilter] = useState('');
    const [tab, setTab]              = useState('list'); // list | attendance | payroll | leave
    const [addModal, setAddModal]    = useState(false);
    const [editItem, setEditItem]    = useState(null);
    const [attendanceModal, setAttendanceModal] = useState(false);
    const [leaveModal, setLeaveModal]           = useState(false);

    useEffect(() => {
        if (isAdmin) {
            axios.get('/api/branches').then(r => setBranches(r.data)).catch(() => {});
        }
    }, [isAdmin]);

    // Attendance state
    const [attendanceData, setAttendanceData] = useState([]);
    const [attMonth, setAttMonth] = useState(new Date().getMonth() + 1);
    const [attYear,  setAttYear]  = useState(new Date().getFullYear());
    const [attLoading, setAttLoading] = useState(false);

    // Leave state
    const [leaves, setLeaves]   = useState([]);
    const [leaveLoading, setLeaveLoading] = useState(false);

    // Payroll state
    const [payrollData, setPayrollData]   = useState([]);
    const [payrollMonth, setPayrollMonth] = useState(new Date().getMonth() + 1);
    const [payrollYear,  setPayrollYear]  = useState(new Date().getFullYear());
    const [payrollLoading, setPayrollLoading] = useState(false);
    const [generating, setGenerating]         = useState(false);
    const [processing, setProcessing]         = useState(false);

    const filtered = employees.filter(e => {
        const matchSearch  = !search || e.name.toLowerCase().includes(search.toLowerCase()) || e.phone?.includes(search);
        const matchRole    = !roleFilter || e.role === roleFilter;
        const matchBranch  = !branchFilter || String(e.branch_id) === String(branchFilter);
        return matchSearch && matchRole && matchBranch;
    });

    const reload = async () => {
        const res = await axios.get('/api/employees');
        setEmployees(res.data.data || res.data);
    };

    const fetchAttendance = useCallback(async () => {
        if (tab !== 'attendance') return;
        setAttLoading(true);
        try {
            const res = await axios.get('/api/employees/attendance/report', { params: { month: attMonth, year: attYear } });
            setAttendanceData(res.data || []);
        } catch { setAttendanceData([]); }
        finally { setAttLoading(false); }
    }, [tab, attMonth, attYear]);

    const fetchLeaves = useCallback(async () => {
        if (tab !== 'leave') return;
        setLeaveLoading(true);
        try {
            const res = await axios.get('/api/employees/leaves');
            setLeaves(res.data.data || res.data || []);
        } catch { setLeaves([]); }
        finally { setLeaveLoading(false); }
    }, [tab]);

    const fetchPayroll = useCallback(async () => {
        if (tab !== 'payroll') return;
        setPayrollLoading(true);
        try {
            const res = await axios.get('/api/employees/payroll', { params: { month: payrollMonth, year: payrollYear } });
            setPayrollData(res.data || []);
        } catch { setPayrollData([]); }
        finally { setPayrollLoading(false); }
    }, [tab, payrollMonth, payrollYear]);

    useEffect(() => { if (initialEmployees.length === 0) reload(); }, []);
    useEffect(() => { fetchAttendance(); }, [fetchAttendance]);
    useEffect(() => { fetchLeaves(); },    [fetchLeaves]);
    useEffect(() => { fetchPayroll(); },   [fetchPayroll]);

    const generatePayroll = async () => {
        setGenerating(true);
        try {
            await axios.post('/api/employees/payroll/generate', { month: payrollMonth, year: payrollYear });
            fetchPayroll();
        } catch (e) { alert(e.response?.data?.message || 'Failed'); }
        finally { setGenerating(false); }
    };

    const processPayroll = async () => {
        const pending = payrollData.filter(p => p.status === 'draft').map(p => p.id);
        if (!pending.length) return alert('No pending payroll to process');
        setProcessing(true);
        try {
            await axios.post('/api/employees/payroll/process', { payment_ids: pending, payment_method: 'bank' });
            fetchPayroll();
        } catch (e) { alert(e.response?.data?.message || 'Failed'); }
        finally { setProcessing(false); }
    };

    const approveLeave = async (leave, action) => {
        try {
            await axios.patch(`/api/employees/leave/${leave.id}/action`, { action });
            fetchLeaves();
        } catch (e) { alert('Failed'); }
    };

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
                        {tab === 'leave' && (
                            <Button size="sm" onClick={() => setLeaveModal(true)}>
                                <Plus className="w-4 h-4" /> Apply Leave
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
                            {isAdmin && branches.length > 0 && (
                                <select value={branchFilter} onChange={e => setBranchFilter(e.target.value)}
                                    className="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                    <option value="">All Branches</option>
                                    {branches.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
                                </select>
                            )}
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
                                            {isAdmin && emp.branch && (
                                                <p className="text-xs text-blue-600 flex items-center gap-1.5">
                                                    <Building2 className="w-3 h-3" /> {emp.branch.name}
                                                </p>
                                            )}
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
                    <div className="p-4">
                        <div className="flex gap-3 mb-4">
                            <div>
                                <label className="block text-xs text-gray-500 mb-1">Month</label>
                                <select value={attMonth} onChange={e => setAttMonth(+e.target.value)}
                                    className="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                    {Array.from({length:12},(_,i)=>i+1).map(m=>(
                                        <option key={m} value={m}>{new Date(2000,m-1).toLocaleString('en',{month:'long'})}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs text-gray-500 mb-1">Year</label>
                                <select value={attYear} onChange={e => setAttYear(+e.target.value)}
                                    className="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                    {[new Date().getFullYear()-1, new Date().getFullYear()].map(y=>(
                                        <option key={y} value={y}>{y}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {attLoading ? (
                            <div className="py-12 text-center text-gray-400">Loading...</div>
                        ) : attendanceData.length === 0 ? (
                            <div className="py-12 text-center">
                                <Clock className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                                <p className="text-gray-500">No attendance records for this period</p>
                                <Button className="mt-4" onClick={() => setAttendanceModal(true)}>
                                    <Clock className="w-4 h-4" /> Mark Attendance
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-left text-xs text-gray-500 border-b border-gray-100">
                                            <th className="pb-2 font-medium">Employee</th>
                                            <th className="pb-2 font-medium">Date</th>
                                            <th className="pb-2 font-medium">Check In</th>
                                            <th className="pb-2 font-medium">Check Out</th>
                                            <th className="pb-2 font-medium">Hours</th>
                                            <th className="pb-2 font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-50">
                                        {attendanceData.map(a => (
                                            <tr key={a.id} className="hover:bg-gray-50/50">
                                                <td className="py-2.5 pr-4 font-medium text-gray-800">{a.employee?.name || '—'}</td>
                                                <td className="py-2.5 pr-4 text-gray-600">{formatDate(a.date)}</td>
                                                <td className="py-2.5 pr-4 text-emerald-600">{a.check_in || '—'}</td>
                                                <td className="py-2.5 pr-4 text-red-600">{a.check_out || '—'}</td>
                                                <td className="py-2.5 pr-4 text-gray-600">{a.hours_worked ?? '—'}h</td>
                                                <td className="py-2.5">
                                                    <span className={cn('text-xs px-2 py-0.5 rounded-full font-medium capitalize',
                                                        a.status === 'present' ? 'bg-emerald-100 text-emerald-700' :
                                                        a.status === 'absent'  ? 'bg-red-100 text-red-700' :
                                                        a.status === 'late'    ? 'bg-amber-100 text-amber-700' :
                                                        'bg-gray-100 text-gray-600'
                                                    )}>
                                                        {a.status || 'present'}
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

                {/* Payroll tab */}
                {tab === 'payroll' && (
                    <div className="p-4">
                        <div className="flex flex-wrap items-center gap-3 mb-4">
                            <div>
                                <label className="block text-xs text-gray-500 mb-1">Month</label>
                                <select value={payrollMonth} onChange={e => setPayrollMonth(+e.target.value)}
                                    className="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                    {Array.from({length:12},(_,i)=>i+1).map(m=>(
                                        <option key={m} value={m}>{new Date(2000,m-1).toLocaleString('en',{month:'long'})}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs text-gray-500 mb-1">Year</label>
                                <select value={payrollYear} onChange={e => setPayrollYear(+e.target.value)}
                                    className="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                    {[new Date().getFullYear()-1, new Date().getFullYear()].map(y=>(
                                        <option key={y} value={y}>{y}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex gap-2 ml-auto">
                                <Button size="sm" variant="outline" onClick={generatePayroll} loading={generating}>
                                    <DollarSign className="w-4 h-4" /> Generate
                                </Button>
                                {payrollData.some(p => p.status === 'draft') && (
                                    <Button size="sm" onClick={processPayroll} loading={processing}>
                                        <CheckCircle className="w-4 h-4" /> Process All
                                    </Button>
                                )}
                            </div>
                        </div>

                        {payrollLoading ? (
                            <div className="py-12 text-center text-gray-400">Loading...</div>
                        ) : payrollData.length === 0 ? (
                            <div className="py-12 text-center">
                                <DollarSign className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                                <p className="text-gray-500">No payroll generated for this period</p>
                                <Button className="mt-4" onClick={generatePayroll} loading={generating}>
                                    <DollarSign className="w-4 h-4" /> Generate Payroll
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-left text-xs text-gray-500 border-b border-gray-100">
                                            <th className="pb-2 font-medium">Employee</th>
                                            <th className="pb-2 font-medium">Basic</th>
                                            <th className="pb-2 font-medium">Allowances</th>
                                            <th className="pb-2 font-medium">Deductions</th>
                                            <th className="pb-2 font-medium">Net Pay</th>
                                            <th className="pb-2 font-medium">Days</th>
                                            <th className="pb-2 font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-50">
                                        {payrollData.map(p => (
                                            <tr key={p.id} className="hover:bg-gray-50/50">
                                                <td className="py-3 pr-4 font-medium text-gray-800">{p.employee?.name || '—'}</td>
                                                <td className="py-3 pr-4">{formatCurrency(p.basic_salary ?? 0)}</td>
                                                <td className="py-3 pr-4 text-emerald-600">+{formatCurrency(p.total_allowances ?? 0)}</td>
                                                <td className="py-3 pr-4 text-red-600">-{formatCurrency(p.total_deductions ?? 0)}</td>
                                                <td className="py-3 pr-4 font-bold text-gray-900">{formatCurrency(p.net_salary ?? 0)}</td>
                                                <td className="py-3 pr-4 text-gray-500 text-xs">{p.present_days}/{p.working_days}</td>
                                                <td className="py-3">
                                                    <span className={cn('text-xs px-2 py-0.5 rounded-full font-medium',
                                                        p.status === 'paid'    ? 'bg-emerald-100 text-emerald-700' :
                                                        p.status === 'draft'   ? 'bg-amber-100 text-amber-700' :
                                                        'bg-gray-100 text-gray-600'
                                                    )}>
                                                        {p.status}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t border-gray-200 font-semibold">
                                            <td className="py-3 text-gray-700">Total</td>
                                            <td colSpan={3}></td>
                                            <td className="py-3 font-bold text-gray-900">
                                                {formatCurrency(payrollData.reduce((s, p) => s + (p.net_salary ?? 0), 0))}
                                            </td>
                                            <td colSpan={2}></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                {/* Leave tab */}
                {tab === 'leave' && (
                    <div className="p-4">
                        {leaveLoading ? (
                            <div className="py-12 text-center text-gray-400">Loading...</div>
                        ) : leaves.length === 0 ? (
                            <div className="py-12 text-center">
                                <Calendar className="w-12 h-12 text-gray-200 mx-auto mb-3" />
                                <p className="text-gray-500">No leave requests found</p>
                                <Button className="mt-4" onClick={() => setLeaveModal(true)}>
                                    <Plus className="w-4 h-4" /> Apply Leave
                                </Button>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {leaves.map(leave => (
                                    <div key={leave.id}
                                        className="flex items-center gap-4 p-4 border border-gray-200 rounded-xl hover:border-violet-200 transition-colors">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2 mb-1">
                                                <p className="font-medium text-gray-800">{leave.employee?.name || '—'}</p>
                                                <span className="text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">
                                                    {leave.leave_type?.name || 'Leave'}
                                                </span>
                                            </div>
                                            <p className="text-sm text-gray-500">
                                                {formatDate(leave.from_date)} – {formatDate(leave.to_date)}
                                                {leave.days && ` (${leave.days} day${leave.days !== 1 ? 's' : ''})`}
                                            </p>
                                            {leave.reason && <p className="text-xs text-gray-400 mt-0.5">{leave.reason}</p>}
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span className={cn('text-xs px-2.5 py-1 rounded-full font-medium',
                                                leave.status === 'approved' ? 'bg-emerald-100 text-emerald-700' :
                                                leave.status === 'rejected' ? 'bg-red-100 text-red-700' :
                                                'bg-amber-100 text-amber-700'
                                            )}>
                                                {leave.status}
                                            </span>
                                            {leave.status === 'pending' && (
                                                <div className="flex gap-1">
                                                    <button onClick={() => approveLeave(leave, 'approve')}
                                                        className="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Approve">
                                                        <CheckCircle className="w-4 h-4" />
                                                    </button>
                                                    <button onClick={() => approveLeave(leave, 'reject')}
                                                        className="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Reject">
                                                        <XCircle className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Modals */}
            {addModal && <EmployeeModal onClose={() => setAddModal(false)} onSaved={() => { setAddModal(false); reload(); }} isAdmin={isAdmin} branches={branches} />}
            {editItem && <EmployeeModal employee={editItem} onClose={() => setEditItem(null)} onSaved={() => { setEditItem(null); reload(); }} isAdmin={isAdmin} branches={branches} />}
            {attendanceModal && <AttendanceModal onClose={() => setAttendanceModal(false)} />}
            {leaveModal && (
                <LeaveModal
                    onClose={() => setLeaveModal(false)}
                    onSaved={() => { setLeaveModal(false); fetchLeaves(); }}
                />
            )}
        </AppLayout>
    );
}
