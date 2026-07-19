<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Models\EmployeeProject;
use Illuminate\Http\Request;
use App\Http\Requests\EmployeeProjectRequest;
use App\Http\Resources\EmployeeProjectResource;
use Carbon\Carbon;

class EmployeeProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee_project.view')->only(['index', 'show']);
        $this->middleware('permission:employee_project.create')->only(['store']);
        // destroy: role (route = superadmin|owner) + in-method tenancy scoping.
        // Not gated on employee_project.delete so owners don't need a re-seed.
    }

    /**
     * List assignments
     */
    public function index(Request $request)
    {
        $assignments = EmployeeProject::with([
            'employee',
            'project',
        ])
            ->whereHas('employee.company', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->when($request->employee_id, function ($q) use ($request) {
                $q->where('employee_id', $request->employee_id);
            })
            ->when($request->project_id, function ($q) use ($request) {
                $q->where('project_id', $request->project_id);
            })
            ->orderByDesc('assigned_at')
            ->paginate(10);

        return ResponseHelper::success(
            EmployeeProjectResource::collection($assignments),
            __('messages.data_retrieved')
        );
    }
    /**
     * Assign employee to project
     */
    public function store(EmployeeProjectRequest $request)
    {
        // prevent duplicate assignment
        $exists = EmployeeProject::where('employee_id', $request->employee_id)
            ->where('project_id', $request->project_id)
            ->exists();

        if ($exists) {
            return ResponseHelper::error(
                __('messages.already_exists'),
                null,
                409
            );
        }

        $assignment = EmployeeProject::create([
            'employee_id' => $request->employee_id,
            'project_id'  => $request->project_id,
            'assigned_at' => Carbon::now(),
        ]);

        return ResponseHelper::success(
            $assignment->load(['employee', 'project']),
            __('messages.data_saved'),
            201
        );
    }

    /**
     * Show single assignment
     */
    public function show($id)
    {
        $assignment = EmployeeProject::with([
            'employee',
            'project'
        ])
            ->findOrFail($id);

        return ResponseHelper::success(
            $assignment,
            __('messages.data_retrieved')
        );
    }

    /**
     * Remove assignment
     */
    public function destroy($id)
    {
        $assignment = EmployeeProject::findOrFail($id);

        // Tenancy scoping: owners may only remove assignments that belong to
        // their own company (resolved via the linked project, then employee).
        $authUser = auth()->user();
        if (! $authUser->hasRole('superadmin')) {
            $companyId = $assignment->project?->company_id
                ?? $assignment->employee?->company_id;
            $ownCompanyIds = \App\Models\Company::where('user_id', $authUser->id)->pluck('id');
            if (! $companyId || ! $ownCompanyIds->contains((int) $companyId)) {
                return ResponseHelper::error(
                    __('messages.company_scope_forbidden'),
                    null,
                    403
                );
            }
        }

        $assignment->delete();

        return ResponseHelper::success(
            $assignment,
            __('messages.data_deleted')
        );
    }
}
