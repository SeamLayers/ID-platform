<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Http\Requests\CompanyBranchRequest;
use App\Http\Resources\CompanyBranchResource;
use Illuminate\Http\Request;

class CompanyBranchController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:company_branch.view')->only(['index', 'show']);
        $this->middleware('permission:company_branch.create')->only(['store']);
        $this->middleware('permission:company_branch.update')->only(['update']);
        // destroy: role (route = superadmin|owner) + in-method tenancy scoping.
        // Not gated on company_branch.delete so owners don't need a re-seed.
    }

    /**
     * List all branches
     */
    public function index(Request $request)
    {
        // The department / employee forms ask for per_page=200 to fill their
        // branch picker; hard-coding 10 here silently truncated those options.
        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 200) : 10;

        $branches = CompanyBranch::notDeleted()
            ->with('company')
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
            ->latest()
            ->paginate($perPage);


        return ResponseHelper::success(
            CompanyBranchResource::collection($branches),
            __('messages.data_retrieved')
        );
    }

    /**
     * Store new branch
     */
    public function store(CompanyBranchRequest $request)
    {
        $data = $request->validated();

        // company_id is client-supplied and only checked for existence, so
        // without this an owner could create a branch inside another tenant.
        if ($denied = $this->denyOutsideOwnCompanies($data['company_id'] ?? null)) {
            return $denied;
        }

        $branch = CompanyBranch::create($data);

        return ResponseHelper::success($branch->load('company'), __('messages.data_saved'), 201);
    }

    /**
     * Show single branch
     */
    public function show($id)
    {
        $branch = CompanyBranch::with('company')
            ->findOrFail($id);

        // Same hole as departments: a bare findOrFail handed any owner any
        // tenant's branch, with the parent company row attached.
        if ($denied = $this->denyOutsideOwnCompanies($branch->company_id)) {
            return $denied;
        }

        return ResponseHelper::success(
            $branch,
            __('messages.data_retrieved'),
            200
        );
    }

    /**
     * 404 unless the caller owns the company, or is a superadmin. 404 rather
     * than 403 so an outsider cannot confirm that an id exists.
     */
    private function denyOutsideOwnCompanies($companyId)
    {
        $user = auth()->user();

        if ($user?->hasRole('superadmin')) {
            return null;
        }

        $owned = \App\Models\Company::where('user_id', $user?->id)->pluck('id');

        if ($companyId === null || ! $owned->contains((int) $companyId)) {
            return ResponseHelper::error(__('messages.data_not_found'), null, 404);
        }

        return null;
    }

    /**
     * Update branch
     */
    public function update(CompanyBranchRequest $request, $id)
    {
        $branch = CompanyBranch::findOrFail($id);

        // Tenancy scoping: owners may only edit their own company's branches.
        $authUser = auth()->user();
        if (! $authUser->hasRole('superadmin')) {
            $ownCompanyIds = \App\Models\Company::where('user_id', $authUser->id)->pluck('id');
            if (! $ownCompanyIds->contains((int) $branch->company_id)) {
                return ResponseHelper::error(__('messages.company_scope_forbidden'), null, 403);
            }
        }

        $branch->update($request->validated());

        return ResponseHelper::success(
            $branch->load('company'),
            __('messages.data_updated')
        );
    }

    /**
     * Delete branch
     */
    public function destroy($id)
    {
        $branch = CompanyBranch::findOrFail($id);

        // Tenancy scoping: owners may only delete branches inside their own
        // company (superadmin is unrestricted). Mirrors the other controllers.
        $authUser = auth()->user();
        if (! $authUser->hasRole('superadmin')) {
            $ownCompanyIds = Company::where('user_id', $authUser->id)->pluck('id');
            if (! $ownCompanyIds->contains((int) $branch->company_id)) {
                return ResponseHelper::error(
                    __('messages.company_scope_forbidden'),
                    null,
                    403
                );
            }
        }

        $branch->delete();

        return ResponseHelper::success(
            $branch,
            __('messages.data_deleted')
        );
    }
}
