<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryController extends Controller
{

    private function transform(Category $category)
    {
        return [
            'id'         => $category->id,
            'name'       => $category->name ?? '',
            'slug'       => $category->slug ?? '',
            'created_by' => $category->created_by ?? null,
            'creator'    => $category->creator ? [
                'id'   => $category->creator->id,
                'name' => $category->creator->name ?? '',
                'email'=> $category->creator->email ?? '',
            ] : new \stdClass(),
            'created_at' => $category->created_at?->toDateTimeString() ?? '',
            'updated_at' => $category->updated_at?->toDateTimeString() ?? '',
        ];
    }

    public function index(Request $request)
    {
        $query = Category::with('creator');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        switch ($request->sort) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        $categories = $query->get()->map(fn($cat) => $this->transform($cat));

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'data'    => $categories
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'slug' => 'nullable|string|unique:categories,slug',
        ]);

        $category = Category::create([
            'name'       => $request->name,
            'slug'       => $request->slug ?? Str::slug($request->name),
            'created_by' => $request->user()->id,
        ]);

        $category->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data'    => $this->transform($category)
        ], 201);
    }

    public function show($id)
    {
        try {
            $category = Category::with('creator')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Category retrieved successfully',
                'data'    => $this->transform($category)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
                'slug' => 'nullable|string|unique:categories,slug,' . $category->id,
            ]);

            $category->update([
                'name' => $request->name,
                'slug' => $request->slug ?? Str::slug($request->name),
            ]);

            $category->load('creator');

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data'    => $this->transform($category)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
                'data'    => new \stdClass() // empty object
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }
}