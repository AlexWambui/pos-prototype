<?php

namespace Modules\StoreBranch\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Exception;
use Modules\StoreBranch\Http\Requests\BranchRequest;
use Modules\StoreBranch\Http\Resources\BranchResource;
use Modules\StoreBranch\Models\Branch;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::query()
            ->search($request->search)
            ->latest()
            ->paginate(15);

        return inertia('app/branches/Index', [
            'branches' => BranchResource::collection($branches),
            'total' => $branches->total(),
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return inertia('app/branches/Create');
    }

    public function store(BranchRequest $request)
    {
        $validated_data = $request->validated();

        try {
            DB::beginTransaction();

            Branch::create($validated_data);

            DB::commit();

            Inertia::flash('toast', [
                'type' => "success",
                'message' => "Branch created successfully"
            ]);

            return to_route('branches.index');
        } catch (Exception $e) {
            DB::rollback();

            Inertia::flash('toast', [
                'type' => "error",
                'message' => "Failed to save branch: {$e->getMessage()}"
            ]);
        }
    }

    public function edit(Branch $branch)
    {
        return inertia('app/branches/Edit', [
            'branch' => new BranchResource($branch)
        ]);
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        $validated_data = $request->validated();

        try {
            DB::beginTransaction();

            $branch->update($validated_data);

            DB::commit();

            Inertia::flash('toast', [
                'type' => "success",
                'message' => "Branch updated successfully"
            ]);

            return to_route('branches.index');
        } catch (Exception $e) {
            DB::rollback();

            Inertia::flash('toast', [
                'type' => "error",
                'message' => "Failed to update branch: {$e->getMessage()}"
            ]);
        }
    }
}