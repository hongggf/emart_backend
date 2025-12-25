<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReviewController extends Controller
{

    private function transform(Review $review)
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment ?? '',
            'user' => [
                'id' => $review->user->id,
                'name' => $review->user->name,
            ],
            'product' => [
                'id' => $review->product->id,
                'name' => $review->product->name,
            ],
            'created_at' => $review->created_at?->toDateTimeString(),
        ];
    }

    public function index(Request $request)
    {
        $query = Review::with(['user', 'product']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $reviews = $query->latest()->get()
            ->map(fn ($r) => $this->transform($r));

        return response()->json([
            'success' => true,
            'message' => 'Reviews retrieved successfully',
            'data' => $reviews
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string',
        ]);

        // Optional: one review per user per product
        $exists = Review::where('user_id', $request->user()->id)
                        ->where('product_id', $request->product_id)
                        ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'You already reviewed this product',
                'data'    => new \stdClass()
            ], 422);
        }

        $review = Review::create([
            'user_id'    => $request->user()->id,
            'product_id' => $request->product_id,
            'rating'     => $request->rating,
            'comment'    => $request->comment,
            'created_by' => $request->user()->id,
        ]);

        $review->load(['user', 'product']);

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully',
            'data' => $this->transform($review)
        ], 201);
    }

    public function show($id)
    {
        try {
            $review = Review::with(['user', 'product'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->transform($review)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found',
                'data' => new \stdClass()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $review = Review::findOrFail($id);

            $request->validate([
                'rating'  => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string',
            ]);

            $review->update([
                'rating'  => $request->rating,
                'comment' => $request->comment,
            ]);

            $review->load(['user', 'product']);

            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully',
                'data' => $this->transform($review)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found',
                'data' => new \stdClass()
            ], 404);
        }
    }

    public function destroy($id)
    {
        try {
            Review::findOrFail($id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully',
                'data' => new \stdClass()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found',
                'data' => new \stdClass()
            ], 404);
        }
    }
}