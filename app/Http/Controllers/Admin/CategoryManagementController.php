<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryManagementController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('quizzes')
            ->orderBy('order')
            ->paginate(15);
        
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());
        
        // Clear cache
        Cache::forget('categories.active');
        
        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(StoreCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        
        // Clear cache
        Cache::forget('categories.active');
        Cache::forget('category.' . $category->slug);
        
        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        // Check if category has quizzes
        if ($category->quizzes()->count() > 0) {
            return back()->with('error', 'Cannot delete category with associated quizzes.');
        }
        
        $category->delete();
        
        // Clear cache
        Cache::forget('categories.active');
        
        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:categories,id',
            'order.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->order as $item) {
            Category::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        Cache::forget('categories.active');

        return response()->json(['success' => true]);
    }
}