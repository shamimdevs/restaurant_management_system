<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\SalaryPayment;
use App\Models\SalaryStructure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $employees = Employee::with(['department', 'designation'])
            ->where('branch_id', $request->user()->branch_id)
            ->active()
            ->orderBy('name')
            ->get();

        return Inertia::render('Employees/Index', ['employees' => $employees]);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('employees', 'public');
        }

        $employee = Employee::create(array_merge($data, [
            'branch_id'   => $request->user()->branch_id,
            'employee_id' => $this->generateEmployeeId($request->user()->branch_id),
        ]));

        // Create salary structure if provided
        if ($request->filled('basic_salary')) {
            SalaryStructure::create([
                'employee_id'    => $employee->id,
                'basic_salary'   => $request->basic_salary,
                'allowances'     => $request->allowances ?? [],
                'deductions'     => $request->deductions ?? [],
                'gross_salary'   => $request->basic_salary + collect($request->allowances ?? [])->sum('amount'),
                'net_salary'     => $request->basic_salary + collect($request->allowances ?? [])->sum('amount') - collect($request->deductions ?? [])->sum('amount'),
                'effective_from' => $request->joining_date ?? today(),
                'is_current'     => true,
            ]);
        }

        return response()->json(['employee' => $employee->load(['department', 'designation']), 'message' => 'Employee added.'], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        return response()->json($employee->load([
            'department', 'designation', 'currentSalaryStructure',
            'attendance' => fn ($q) => $q->whereMonth('date', now()->month)->orderBy('date', 'desc'),
        ]));
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $employee->update($request->only('name', 'phone', 'email', 'department_id', 'designation_id', 'status', 'address', 'city'));
        return response()->json(['employee' => $employee->fresh(['department', 'designation']), 'message' => 'Employee updated.']);
    }

    // ── Attendance ───────────────────────────────────────────────────────

    public function markAttendance(Request $request): JsonResponse
    {
        $request->validate([
            'records' => 'required|array',
            'records.*.employee_id' => 'required|exists:employees,id',
            'records.*.status'      => 'required|in:present,absent,late,half_day,leave,holiday',
            'records.*.check_in'    => 'nullable|date_format:H:i',
            'records.*.check_out'   => 'nullable|date_format:H:i',
        ]);

        foreach ($request->records as $record) {
            $hoursWorked = 0;
            if (! empty($record['check_in']) && ! empty($record['check_out'])) {
                $in  = \Carbon\Carbon::createFromFormat('H:i', $record['check_in']);
                $out = \Carbon\Carbon::createFromFormat('H:i', $record['check_out']);
                $hoursWorked = round($in->floatDiffInHours($out), 2);
            }

            Attendance::updateOrCreate(
                ['employee_id' => $record['employee_id'], 'date' => today()],
                array_merge($record, [
                    'branch_id'   => $request->user()->branch_id,
                    'hours_worked'=> $hoursWorked,
                ])
            );
        }

        return response()->json(['message' => 'Attendance marked.']);
    }

    public function getAttendance(Request $request): JsonResponse
    {
        $request->validate(['month' => 'required|integer', 'year' => 'required|integer']);

        $attendance = Attendance::with('employee:id,name,employee_id')
            ->where('branch_id', $request->user()->branch_id)
            ->month($request->month, $request->year)
            ->get();

        return response()->json($attendance);
    }

    // ── Leave ────────────────────────────────────────────────────────────

    public function applyLeave(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'from_date'     => 'required|date',
            'to_date'       => 'required|date|after_or_equal:from_date',
            'reason'        => 'nullable|string',
        ]);

        $from = \Carbon\Carbon::parse($request->from_date);
        $to   = \Carbon\Carbon::parse($request->to_date);

        $leave = LeaveRequest::create(array_merge($request->validated(), [
            'days' => $from->diffInDaysFiltered(fn ($d) => ! $d->isWeekend(), $to) + 1,
        ]));

        return response()->json(['leave' => $leave, 'message' => 'Leave applied.'], 201);
    }

    public function approveLeave(Request $request, LeaveRequest $leave): JsonResponse
    {
        $leave->update([
            'status'           => $request->action === 'approve' ? 'approved' : 'rejected',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'rejection_reason' => $request->reason ?? null,
        ]);

        return response()->json(['message' => ucfirst($request->action) . 'd successfully.']);
    }

    // ── Payroll ──────────────────────────────────────────────────────────

    public function generatePayroll(Request $request): JsonResponse
    {
        $request->validate(['month' => 'required|integer|min:1|max:12', 'year' => 'required|integer']);

        $branchId  = $request->user()->branch_id;
        $employees = Employee::where('branch_id', $branchId)->active()->with('currentSalaryStructure')->get();

        $generated = [];
        foreach ($employees as $employee) {
            // Skip if payroll already generated
            if (SalaryPayment::where('employee_id', $employee->id)->where('month', $request->month)->where('year', $request->year)->exists()) {
                continue;
            }

            $structure = $employee->currentSalaryStructure;
            $presentDays = Attendance::where('employee_id', $employee->id)->month($request->month, $request->year)->present()->count();
            $workingDays = cal_days_in_month(CAL_GREGORIAN, $request->month, $request->year);

            $proratedSalary = $structure ? round($structure->basic_salary * $presentDays / max($workingDays, 1), 2) : 0;
            $allowances     = $structure ? $structure->total_allowances : 0;
            $deductions     = $structure ? $structure->total_deductions : 0;
            $netSalary      = $proratedSalary + $allowances - $deductions;

            $payment = SalaryPayment::create([
                'employee_id'        => $employee->id,
                'branch_id'          => $branchId,
                'salary_structure_id'=> $structure?->id,
                'payroll_reference'  => 'SAL-' . now()->format('Ym') . '-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
                'month'              => $request->month,
                'year'               => $request->year,
                'basic_salary'       => $proratedSalary,
                'total_allowances'   => $allowances,
                'total_deductions'   => $deductions,
                'net_salary'         => $netSalary,
                'working_days'       => $workingDays,
                'present_days'       => $presentDays,
                'status'             => 'draft',
            ]);

            $generated[] = $payment;
        }

        return response()->json(['payroll' => $generated, 'message' => count($generated) . ' payroll records generated.']);
    }

    public function processPayroll(Request $request): JsonResponse
    {
        $request->validate(['payment_ids' => 'required|array', 'payment_method' => 'required|string']);

        SalaryPayment::whereIn('id', $request->payment_ids)->update([
            'status'           => 'paid',
            'payment_date'     => today(),
            'payment_method'   => $request->payment_method,
            'paid_by'          => $request->user()->id,
        ]);

        return response()->json(['message' => 'Payroll processed.']);
    }

    // ── Private ──────────────────────────────────────────────────────────

    private function generateEmployeeId(int $branchId): string
    {
        $branch = \App\Models\Branch::find($branchId);
        $count  = Employee::where('branch_id', $branchId)->count();
        return 'EMP-' . strtoupper($branch->code) . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
