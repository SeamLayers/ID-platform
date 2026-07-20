<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;

use App\Http\Helpers\ResponseHelper;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:project.view')->only(['index', 'show']);
        $this->middleware('permission:project.create')->only(['store']);
        $this->middleware('permission:project.update')->only(['update']);
        // destroy: role (route = superadmin|owner) + in-method tenancy scoping.
        // Not gated on project.delete so owners don't need the seeder re-run.
    }

    /**
     * List projects
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 200) : 10;

        $projects = Project::with([
            'employees',
            'company',
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
            // filled(), not truthiness: a literal "0" is a valid term. The
            // OR-chain lives in a scope so it stays grouped against tenancy.
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->search($request->input('search'));
            })
            ->latest()
            ->paginate($perPage);

        return ResponseHelper::success(
            ProjectResource::collection($projects),
            __('messages.data_retrieved')
        );
    }
    /**
     * Create project
     */
    public function store(ProjectRequest $request)
    {
        $project = Project::create($request->validated());

        // Attach employees if provided
        if ($request->has('employee_ids')) {
            $project->employees()->sync($request->employee_ids);
        }

        return ResponseHelper::success(
            $project->load(['employees', 'company']),
            __('messages.data_saved'),
            201
        );
    }

    /**
     * Show project
     */
    public function show($id)
    {
        $project = Project::with(['employees', 'company'])
            ->findOrFail($id);

        return ResponseHelper::success(
            $project,
            __('messages.data_retrieved')
        );
    }

    /**
     * Update project
     */
    public function update(ProjectRequest $request, $id)
    {
        $project = Project::findOrFail($id);

        // Tenancy scoping: owners may only edit their own company's projects
        // (prevents a cross-tenant IDOR write via a guessed project id).
        $authUser = auth()->user();
        if (! $authUser->hasRole('superadmin')) {
            $ownCompanyIds = \App\Models\Company::where('user_id', $authUser->id)->pluck('id');
            if (! $ownCompanyIds->contains((int) $project->company_id)) {
                return ResponseHelper::error(__('messages.company_scope_forbidden'), null, 403);
            }
        }

        $project->update($request->validated());

        // Sync employees if provided
        if ($request->has('employee_ids')) {
            $project->employees()->sync($request->employee_ids);
        }

        return ResponseHelper::success(
            $project->load(['employees', 'company']),
            __('messages.data_updated')
        );
    }

    /**
     * Delete project
     */
    public function destroy($id)
    {
        $project = Project::findOrFail($id);

        // Tenancy scoping: owners may only delete their own company's projects.
        $authUser = auth()->user();
        if (! $authUser->hasRole('superadmin')) {
            $ownCompanyIds = \App\Models\Company::where('user_id', $authUser->id)->pluck('id');
            if (! $ownCompanyIds->contains((int) $project->company_id)) {
                return ResponseHelper::error(
                    __('messages.company_scope_forbidden'),
                    null,
                    403
                );
            }
        }

        $project->delete();

        return ResponseHelper::success(
            $project,
            __('messages.data_deleted')
        );
    }
}
