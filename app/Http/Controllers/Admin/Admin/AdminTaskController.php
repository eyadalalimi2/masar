<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTaskController extends Controller
{
    public function index(): View
    {
        $tasks = Task::query()->latest()->paginate(15);

        return view('admin.tasks.index', compact('tasks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:4000'],
        ]);

        Task::query()->create($data);

        return redirect()->route('admin.tasks.index')->with('success', 'تمت إضافة المهمة بنجاح.');
    }

    public function edit(Task $task): View
    {
        return view('admin.tasks.edit', compact('task'));
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:4000'],
        ]);

        $task->update($data);

        return redirect()->route('admin.tasks.index')->with('success', 'تم تعديل المهمة بنجاح.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()->route('admin.tasks.index')->with('success', 'تمت إزالة المهمة.');
    }
}
