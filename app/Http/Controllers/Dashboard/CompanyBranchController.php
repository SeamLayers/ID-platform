<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Http\Requests\CompanyBranchRequest;
use App\Http\Resources\CompanyBranchResource;

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
    public function index()
    {


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
            ->paginate(10);


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
        $branch = CompanyBranch::create($request->validated());

        return ResponseHelper::success($branch->load('company'), __('messages.data_saved'), 201);
    }

    /**
     * Show single branch
     */
    public function show($id)
    {
        $branch = CompanyBranch::with('company')
            ->findOrFail($id);

        return ResponseHelper::success(
            $branch,
            __('messages.data_retrieved'),
            200
        );
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
