<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderItemController extends Controller
{

    private function transform(OrderItem $item)
    {
        return [
            'id' => $item->id,
            'order_id' => $item->order_id,
            'product' => [
                'id'    => $item->product->id,
                'name'  => $item->product->name,
                'price' => $item->price,
            ],
            'quantity' => $item->quantity,
            'subtotal' => $item->quantity * $item->price,
            'created_at' => $item->created_at?->toDateTimeString(),
        ];
    }

    public function index($orderId)
    {
        $items = OrderItem::with('product')
            ->where('order_id', $orderId)
            ->get()
            ->map(fn ($i) => $this->transform($i));

        return response()->json([
            'success' => true,
            'message' => 'Order items retrieved successfully',
            'data'    => $items
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id'   => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        $item = OrderItem::create([
            'order_id'  => $request->order_id,
            'product_id'=> $product->id,
            'quantity'  => $request->quantity,
            'price'     => $product->price, // price at order time
            'created_by'=> $request->user()->id,
        ]);

        $item->load('product');

        return response()->json([
            'success' => true,
            'message' => 'Order item added successfully',
            'data'    => $this->transform($item)
        ], 201);
    }

    public function show($id)
    {
        try {
            $item = OrderItem::with('product')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => $this->transform($item)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $item = OrderItem::findOrFail($id);

            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $item->update([
                'quantity' => $request->quantity
            ]);

            $item->load('product');

            return response()->json([
                'success' => true,
                'message' => 'Order item updated successfully',
                'data'    => $this->transform($item)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    public function destroy($id)
    {
        try {
            OrderItem::findOrFail($id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order item deleted successfully',
                'data'    => new \stdClass()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }
}