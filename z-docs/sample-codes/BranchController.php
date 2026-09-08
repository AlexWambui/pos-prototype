<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\Branches\BranchRequest;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::query()
            ->search($request->search)
            ->latest()
            ->paginate(15);

        return Inertia::render('branches/Index', [
            'branches' => $branches->items(),
            'total' => $branches->total(),
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return Inertia::render('branches/Create', [
            'breadcrumbs' => [
                ['title' => 'Branches', 'href' => route('branches.index')],
                ['title' => 'Create Branch', 'href' => route('branches.create')],
            ]
        ]);
    }

    public function store(BranchRequest $request)
    {
        $validated = $request->validated();

        Branch::create($validated);

        return redirect()
            ->route('branches.index')
            ->with(
                [
                    'message' => 'Branch created successfully', 
                    'type' => 'success'
                ]
            );
    }

    public function edit(Branch $branch)
    {
        return Inertia::render('branches/Edit', [
            'branch' => $branch,
            'breadcrumbs' => [
                ['title' => 'Branches', 'href' => route('branches.index')],
                ['title' => 'Edit Branch', 'href' => route('branches.edit', $branch->id)],
            ]
        ]);
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        $validated = $request->validated();

        $branch->update($validated);

        return redirect()
            ->route('branches.index')
            ->with(
                [
                    'message' => 'Branch updated successfully', 
                    'type' => 'success'
                ]
            );
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return redirect()
            ->route('branches.index')
            ->with(
                [
                    'message' => 'Branch deleted successfully', 
                    'type' => 'success'
                ]
            );
    }
}
