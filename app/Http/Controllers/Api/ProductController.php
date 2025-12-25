<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{

    private function transform(Product $product)
    {
        return [
            'id'              => $product->id ?? '',
            'name'            => $product->name ?? '',
            'category_id'     => $product->category_id ?? '',
            'category'        => $product->category ? [
                'id'   => $product->category->id,
                'name' => $product->category->name
            ] : new \stdClass(),
            'description'     => $product->description ?? '',
            'price'           => $product->price ?? 0,
            'compare_price'   => $product->compare_price ?? 0,
            'sku'             => $product->sku ?? '',
            'image'           => $product->image 
                                    ? asset('storage/' . $product->image) 
                                    : '',
            'status'          => $product->status ?? '',
            'stock_quantity'  => $product->stock_quantity ?? 0,
            'low_stock_alert' => $product->low_stock_alert ?? 0,
            'creator'         => $product->creator ? [
                'id'    => $product->creator->id,
                'name'  => $product->creator->name,
                'email' => $product->creator->email
            ] : new \stdClass(),
            'created_at'      => $product->created_at?->toDateTimeString(),
            'updated_at'      => $product->updated_at?->toDateTimeString(),
        ];
    }

    // ===================== INDEX =====================
    public function index(Request $request)
    {
        $query = Product::with(['creator', 'category']);

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Sorting
        switch ($request->sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate($request->input('per_page', 20));
        $products->getCollection()->transform(fn ($p) => $this->transform($p));

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data'    => $products
        ]);
    }

    // ===================== STORE =====================
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'category_id'     => 'nullable|exists:categories,id',
            'description'     => 'nullable|string',
            'price'           => 'required|numeric|min:0',
            'compare_price'   => 'nullable|numeric|min:0',
            'image'           => 'nullable|image|max:2048',
            'status'          => 'required|in:active,inactive',
            'stock_quantity'  => 'required|integer|min:0',
            'low_stock_alert' => 'nullable|integer|min:0',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'name'            => $request->name,
            'category_id'     => $request->category_id,
            'description'     => $request->description,
            'price'           => $request->price,
            'compare_price'   => $request->compare_price ?? 0,
            'image'           => $imagePath ?? '',
            'status'          => $request->status,
            'stock_quantity'  => $request->stock_quantity,
            'low_stock_alert' => $request->low_stock_alert ?? 0,
            'created_by'      => $request->user()->id ?? null,
        ]);

        $product->load(['creator', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data'    => $this->transform($product)
        ], 201);
    }

    // ===================== SHOW =====================
    public function show($id)
    {
        try {
            $product = Product::with(['creator', 'category'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Product retrieved successfully',
                'data'    => $this->transform($product)
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    // ===================== UPDATE =====================
    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            // Validate input
            $request->validate([
                'name'            => 'required|string|max:255',
                'category_id'     => 'nullable|exists:categories,id',
                'description'     => 'nullable|string',
                'price'           => 'required|numeric|min:0',
                'compare_price'   => 'nullable|numeric|min:0',
                'image'           => 'nullable|image|max:2048',
                'status'          => 'required|in:active,inactive',
                'stock_quantity'  => 'required|integer|min:0',
                'low_stock_alert' => 'nullable|integer|min:0',
            ]);

            // Prepare data to update
            $data = $request->only([
                'name',
                'category_id',
                'description',
                'price',
                'compare_price',
                'status',
                'stock_quantity',
                'low_stock_alert',
            ]);

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($product->image) {
                    \Storage::disk('public')->delete($product->image);
                }
                // Store new image and update $data
                $data['image'] = $request->file('image')->store('products', 'public');
            }

            // Update product
            $product->update($data);

            // Load relationships
            $product->load(['creator', 'category']);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data'    => $this->transform($product)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    // ===================== DELETE =====================
    public function destroy($id)
    {
    
        try {
            $product = Product::findOrFail($id);

            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
                'data'    => new \stdClass()
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    // ===================== AVAILABLE PRODUCTS (BY TOKEN) =====================
    public function availableProducts(Request $request)
    {
        // Auth user from token (Sanctum / Passport / JWT)
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'data'    => new \stdClass()
            ], 401);
        }

        $query = Product::with(['creator', 'category'])
            ->where('stock_quantity', '>', 0); // ❌ Exclude out of stock

        // 🔍 Search by product name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // 📦 Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // 🔃 Sorting
        switch ($request->sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;

            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;

            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;

            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;

            case 'latest':
                $query->latest();
                break;

            default:
                $query->latest();
        }

        // 📄 Pagination
        $products = $query->paginate($request->input('per_page', 20));

        // 🔁 Transform data
        $products->getCollection()->transform(
            fn ($product) => $this->transform($product)
        );

        return response()->json([
            'success' => true,
            'message' => 'Available products retrieved successfully',
            'data'    => $products
        ]);
    }

}