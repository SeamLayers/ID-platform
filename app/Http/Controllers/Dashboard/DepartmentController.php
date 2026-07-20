<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Http\Requests\DepartmentRequest;
use App\Http\Resources\DepartmentResource;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:department.view')->only(['index', 'show']);
        $this->middleware('permission:department.create')->only(['store']);
        $this->middleware('permission:department.update')->only(['update']);
        // destroy: role (route = superadmin|owner) + in-method tenancy scoping.
        // Not gated on department.delete so behaviour matches the other
        // owner-managed resources and never depends on a seeder re-run.
    }

    /**
     * List departments
     */
    public function index(Request $request)
    {
        // The employee form asks for per_page=200 to fill its department
        // picker; hard-coding 10 here silently truncated those options.
        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 200) : 10;

        $departments = Department::notDeleted()
            ->with([
                'company',
                'employees'
            ])
            // whereHas stays UNCONDITIONAL: besides tenancy it also enforces the
            // parent's soft-delete scope, so rows orphaned by a deleted company
            // (delete doesn't cascade) never reach a Resource that dereferences
            // ->company and 500s. Only the ownership predicate is role-dependent.
            ->whereHas('company', function ($q) {
                $q->when(
                    ! auth()->user()?->hasRole('superadmin'),
                    fn ($c) => $c->where('user_id', auth()->id())
                );
            })
            ->when($request->company_id, function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            // filled(), not truthiness: a literal "0" is a valid term.
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->search($request->input('search'));
            })
            ->latest()
            ->paginate($perPage);

        return ResponseHelper::success(
            DepartmentResource::collection($departments),
            __('messages.data_retrieved')
        );
    }
    /**
     * Store department
     */
    public function store(DepartmentRequest $request)
    {
        $data = $request->validated();

        // company_id arrives from the client and was only checked for
        // existence, so an owner could post another company's id and plant a
        // department inside a tenant they have nothing to do with. index(),
        // update() and destroy() all scope; store() did not.
        if ($denied = $this->denyOutsideOwnCompanies($data['company_id'] ?? null)) {
            return $denied;
        }

        $department = Department::create($data);

        return ResponseHelper::success(
            $department->load(['company', 'employees']),
            __('messages.data_saved'),
            201
        );
    }

    /**
     * Show department
     */
    public function show($id)
    {
        $department = Department::with([
            'company',
            'employees'
        ])
            ->findOrFail($id);

        // A bare findOrFail served any owner any tenant's department by id —
        // and the eager-loaded `company` came with it, so the response carried
        // another business's commercial register, phone and email.
        if ($denied = $this->denyOutsideOwnCompanies($department->company_id)) {
            return $denied;
        }

        return ResponseHelper::success(
            $department,
            __('messages.data_retrieved')
        );
    }

    /**
     * 404 unless the caller owns the company, or is a superadmin.
     *
     * 404 rather than 403 on purpose: telling an outsider "this exists but is
     * not yours" is itself a disclosure — it confirms the id is real.
     */
    private function denyOutsideOwnCompanies($companyId)
    {
        $user = auth()->user();

        if ($user?->hasRole('superadmin')) {
            return null;
        }

        $owned = \App\Models\Company::where('user_id', $user?->id)->pluck('id');

        if ($companyId === null || ! $owned->contains((int) $companyId)) {
            return ResponseHelper::error(__('messages.department_not_found'), null, 404);
        }

        return null;
    }

    /**
     * Update department
     */
    public function update(DepartmentRequest $request, $id)
    {
        $department = Department::findOrFail($id);

        // Tenancy scoping: owners may only edit their own company's departments.
        $authUser = auth()->user();
        if (! $authUser->hasRole('superadmin')) {
            $ownCompanyIds = \App\Models\Company::where('user_id', $authUser->id)->pluck('id');
            if (! $ownCompanyIds->contains((int) $department->company_id)) {
                return ResponseHelper::error(__('messages.department_not_found'), null, 404);
            }
        }

        $department->update($request->validated());

        return ResponseHelper::success(
            $department->load(['company', 'employees']),
            __('messages.data_updated')
        );
    }

    /**
     * Delete department
     *
     * Owners hold the department.delete permission but must stay inside
     * their own tenancy: resolve their company the same way
     * CompanyController@show does (companies.user_id = auth user) and
     * refuse departments that belong to anyone else. Superadmin stays
     * unrestricted.
     */
    public function destroy($id)
    {
        $department = Department::findOrFail($id);

        $user = auth()->user();

        if (! $user->hasRole('superadmin')) {

            // pluck()->contains(): an owner can own several companies
            // (companies.user_id isn't unique), so match against all of them.
            $ownCompanyIds = Company::where('user_id', $user->id)->pluck('id');

            // 404 (not 403) so cross-tenant probing can't confirm that a
            // department id exists — mirrors the company_not_found pattern.
            if (! $ownCompanyIds->contains((int) $department->company_id)) {
                return ResponseHelper::error(
                    __('messages.department_not_found'),
                    null,
                    404
                );
            }
        }

        $department->delete();

        return ResponseHelper::success(
            $department,
            __('messages.data_deleted')
        );
    }
}
