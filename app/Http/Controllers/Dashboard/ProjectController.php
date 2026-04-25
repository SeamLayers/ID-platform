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
        $this->middleware('permission:project.delete')->only(['destroy']);
    }

    /**
     * List projects
     */
    public function index(Request $request)
    {
        $projects = Project::with([
            'employees',
        ])
            ->when($request->company_id, function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            })
            ->latest()
            ->paginate(10);

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
            $project->load('employees'),
            __('messages.data_saved'),
            201
        );
    }

    /**
     * Show project
     */
    public function show($id)
    {
        $project = Project::with('employees')
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

        $project->update($request->validated());

        // Sync employees if provided
        if ($request->has('employee_ids')) {
            $project->employees()->sync($request->employee_ids);
        }

        return ResponseHelper::success(
            $project->load('employees'),
            __('messages.data_updated')
        );
    }

    /**
     * Delete project
     */
    public function destroy($id)
    {
        $project = Project::findOrFail($id);

        $project->delete();

        return ResponseHelper::success(
            $project,
            __('messages.data_deleted')
        );
    }
}
