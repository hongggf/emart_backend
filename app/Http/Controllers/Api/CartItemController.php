<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CartItemController extends Controller
{
    // ================= TRANSFORM FUNCTION =================
    private function transform(CartItem $item)
    {
        return [
            'id'       => $item->id,
            'quantity' => $item->quantity,

            // Product details
            'product'  => $item->product ? [
                'id'              => $item->product->id,
                'name'            => $item->product->name ?? '',
                'category_id'     => $item->product->category_id ?? null,
                'category'        => $item->product->category ? [
                    'id'   => $item->product->category->id,
                    'name' => $item->product->category->name ?? '',
                ] : new \stdClass(),
                'description'     => $item->product->description ?? '',
                'price'           => $item->product->price ?? 0,
                'compare_price'   => $item->product->compare_price ?? 0,
                'sku'             => $item->product->sku ?? '',
                'image'           => $item->product->image ? asset('storage/' . $item->product->image) : null,
                'status'          => $item->product->status ?? '',
                'stock_quantity'  => $item->product->stock_quantity ?? 0,
                'low_stock_alert' => $item->product->low_stock_alert ?? 0,
                'creator'         => $item->product->creator ? [
                    'id'    => $item->product->creator->id,
                    'name'  => $item->product->creator->name ?? '',
                    'email' => $item->product->creator->email ?? '',
                ] : new \stdClass(),
                'created_at'      => $item->product->created_at?->toDateTimeString() ?? '',
                'updated_at'      => $item->product->updated_at?->toDateTimeString() ?? '',
            ] : new \stdClass(),

            // User details
            'user' => $item->user ? [
                'id'         => $item->user->id,
                'name'       => $item->user->name ?? '',
                'email'      => $item->user->email ?? '',
                'phone'      => $item->user->phone ?? null,
                'role'       => $item->user->role ?? '',
                'avatar'     => $item->user->avatar ? asset('storage/' . $item->user->avatar) : null,
                'created_by' => $item->user->created_by ?? $item->user->id,
                'creator'    => $item->user->creator ? [
                    'id'    => $item->user->creator->id,
                    'name'  => $item->user->creator->name ?? '',
                    'email' => $item->user->creator->email ?? '',
                ] : [
                    'id'    => $item->user->id,
                    'name'  => $item->user->name ?? '',
                    'email' => $item->user->email ?? '',
                ],
                'created_at' => $item->user->created_at?->toDateTimeString() ?? '',
                'updated_at' => $item->user->updated_at?->toDateTimeString() ?? '',
            ] : new \stdClass(),

            'created_at' => $item->created_at?->toDateTimeString() ?? '',
        ];
    }

    // ================= INDEX FOR CUSTOMER =================
    // Show cart items of own user token (with search & sort)
    public function index(Request $request)
    {
        $query = CartItem::with(['product.category', 'product.creator', 'user'])
            ->where('user_id', $request->user()->id);

        // Search by product name
        if ($request->filled('search')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting: product price or name
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->join('products', 'cart_items.product_id', '=', 'products.id')
                          ->orderBy('products.price', 'asc');
                    break;
                case 'price_desc':
                    $query->join('products', 'cart_items.product_id', '=', 'products.id')
                          ->orderBy('products.price', 'desc');
                    break;
                case 'name_asc':
                    $query->join('products', 'cart_items.product_id', '=', 'products.id')
                          ->orderBy('products.name', 'asc');
                    break;
                case 'name_desc':
                    $query->join('products', 'cart_items.product_id', '=', 'products.id')
                          ->orderBy('products.name', 'desc');
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        $items = $query->get()->map(fn($i) => $this->transform($i));

        return response()->json([
            'success' => true,
            'message' => 'Cart items retrieved successfully',
            'data'    => $items
        ]);
    }

    // ================= INDEX FOR ADMIN =================
    // Show all cart items (admin only) with search & sort
    public function adminIndex(Request $request)
    {
        $query = CartItem::with(['product.category', 'product.creator', 'user']);

        // Search by product name or user name
        if ($request->filled('search')) {
            $query->whereHas('product', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
                  ->orWhereHas('user', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'));
        }

        // Sorting
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->join('products', 'cart_items.product_id', '=', 'products.id')
                          ->orderBy('products.price', 'asc');
                    break;
                case 'price_desc':
                    $query->join('products', 'cart_items.product_id', '=', 'products.id')
                          ->orderBy('products.price', 'desc');
                    break;
                case 'name_asc':
                    $query->join('products', 'cart_items.product_id', '=', 'products.id')
                          ->orderBy('products.name', 'asc');
                    break;
                case 'name_desc':
                    $query->join('products', 'cart_items.product_id', '=', 'products.id')
                          ->orderBy('products.name', 'desc');
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        $items = $query->get()->map(fn($i) => $this->transform($i));

        return response()->json([
            'success' => true,
            'message' => 'All cart items retrieved successfully',
            'data'    => $items
        ]);
    }

    // ================= STORE =================
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        // If product already in cart → increase quantity
        $item = CartItem::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($item) {
            $item->quantity += $request->quantity;
            $item->save();
        } else {
            $item = CartItem::create([
                'user_id'    => $request->user()->id,
                'product_id' => $request->product_id,
                'quantity'   => $request->quantity,
                'created_by' => $request->user()->id,
            ]);
        }

        $item->load(['product.category', 'product.creator', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart',
            'data'    => $this->transform($item)
        ], 201);
    }

    // ================= UPDATE =================
    public function update(Request $request, $id)
    {
        try {
            $item = CartItem::where('user_id', $request->user()->id)
                ->findOrFail($id);

            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $item->update(['quantity' => $request->quantity]);
            $item->load(['product.category', 'product.creator', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Cart updated successfully',
                'data'    => $this->transform($item)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    // ================= DESTROY =================
    public function destroy(Request $request, $id)
    {
        try {
            $item = CartItem::where('user_id', $request->user()->id)
                ->findOrFail($id);

            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cart item removed',
                'data'    => new \stdClass()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }
}