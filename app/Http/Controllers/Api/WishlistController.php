<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WishlistController extends Controller
{

    private function transform(Wishlist $item)
    {
        return [
            'id' => $item->id,
            'product' => [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'price' => $item->product->price,
            ],
            'created_at' => $item->created_at?->toDateTimeString(),
        ];
    }

    public function index(Request $request)
    {
        $items = Wishlist::with('product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn ($i) => $this->transform($i));

        return response()->json([
            'success' => true,
            'message' => 'Wishlist retrieved successfully',
            'data' => $items
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        // Prevent duplicate wishlist item
        $exists = Wishlist::where('user_id', $request->user()->id)
                          ->where('product_id', $request->product_id)
                          ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Product already in wishlist',
                'data' => new \stdClass()
            ], 422);
        }

        $item = Wishlist::create([
            'user_id'    => $request->user()->id,
            'product_id' => $request->product_id,
            'created_by' => $request->user()->id,
        ]);

        $item->load('product');

        return response()->json([
            'success' => true,
            'message' => 'Product added to wishlist',
            'data' => $this->transform($item)
        ], 201);
    }

    public function destroy($id)
    {
        try {
            Wishlist::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail()
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Wishlist item removed',
                'data' => new \stdClass()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist item not found',
                'data' => new \stdClass()
            ], 404);
        }
    }
}