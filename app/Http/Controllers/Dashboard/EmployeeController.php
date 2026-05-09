<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\Request;


class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee.view')->only(['index', 'show']);
        $this->middleware('permission:employee.create')->only(['store']);
        $this->middleware('permission:employee.update')->only(['update']);
        $this->middleware('permission:employee.delete')->only(['destroy']);
    }

    /**
     * List employees
     */
    public function index(Request $request)
    {
        $employees = Employee::notDeleted()
            ->with([
                'company',
                'branch',
                'role',
                'department',
                'user',
                'projects',
                'businessCard'
            ])
            ->when($request->company_id, function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->when($request->branch_id, function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            })
            ->latest()
            ->paginate(10);

        return ResponseHelper::success(
            EmployeeResource::collection($employees),
            __('messages.data_retrieved')
        );
    }

    /**
     * Create employee
     */
    public function store(EmployeeRequest $request)
    {
        $data = $request->validated();

        $employee = Employee::create($data);

        // Upload employee logo
        if ($request->hasFile('logo')) {
            $employee->addMediaFromRequest('logo')
                ->toMediaCollection('employee_logo');
        }

        return ResponseHelper::success(
            $employee->load([
                'company',
                'branch',
                'role',
                'department',
                'user'
            ]),
            __('messages.data_saved'),
            201
        );
    }

    /**
     * Show single employee
     */
    public function show($id)
    {
        $employee = Employee::with([
            'company',
            'branch',
            'role',
            'department',
            'user',
            'projects',
            'businessCard'
        ])
            ->findOrFail($id);

        return ResponseHelper::success(
            $employee,
            __('messages.data_retrieved')
        );
    }

    /**
     * Update employee
     */
    public function update(EmployeeRequest $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $employee->update($request->validated());

        // Replace logo if uploaded
        if ($request->hasFile('logo')) {
            $employee->clearMediaCollection('employee_logo');

            $employee->addMediaFromRequest('logo')
                ->toMediaCollection('employee_logo');
        }

        return ResponseHelper::success(
            $employee->load([
                'company',
                'branch',
                'role',
                'department',
                'user'
            ]),
            __('messages.data_updated')
        );
    }

    /**
     * Delete employee
     */
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        $employee->delete();

        return ResponseHelper::success(
            $employee,
            __('messages.data_deleted')
        );
    }
}
