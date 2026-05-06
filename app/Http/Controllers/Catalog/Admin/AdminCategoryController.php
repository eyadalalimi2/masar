<?php

namespace App\Http\Controllers\Catalog\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Category;
use App\Http\Requests\Catalog\CategoryRequest;
use App\Services\Catalog\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(): View
    {
        $categories = Category::with('parent')->latest()->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.categories.create', compact('categories'));
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->categoryService->create($request->validated());

        return redirect()->route('admin.categories.index')->with('success', 'تم إضافة التصنيف بنجاح.');
    }

    public function edit(Category $category): View
    {
        $categories = Category::where('id', '!=', $category->id)->orderBy('name')->get(['id', 'name']);

        return view('admin.categories.edit', compact('category', 'categories'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categoryService->update($category, $request->validated());

        return redirect()->route('admin.categories.index')->with('success', 'تم تعديل التصنيف بنجاح.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categoryService->delete($category);

        return redirect()->route('admin.categories.index')->with('success', 'تم حذف التصنيف بنجاح.');
    }
}