<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
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
        $this->middleware('permission:company_branch.delete')->only(['destroy']);
    }

    /**
     * List all branches
     */
    public function index()
    {
        $branches = CompanyBranch::notDeleted()
            ->with('company')
            ->whereHas('company', function ($query) {
                $query->where('user_id', auth()->id());
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

        $branch->delete();

        return ResponseHelper::success(
            $branch,
            __('messages.data_deleted')
        );
    }
}
