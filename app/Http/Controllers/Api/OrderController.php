<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderController extends Controller
{

    private function transform(Order $order)
    {
        return [
            'id' => $order->id,
            'amounts' => [
                'subtotal'      => $order->subtotal,
                'shipping_fee'  => $order->shipping_fee,
                'discount'      => $order->discount,
                'total_amount'  => $order->total_amount,
            ],
            'status' => [
                'order'   => $order->status,
                'payment' => $order->payment_status,
            ],
            'user' => [
                'id'    => $order->user->id,
                'name'  => $order->user->name ?? '',
                'email' => $order->user->email ?? '',
            ],
            'address_id' => $order->address_id,
            'created_at' => $order->created_at?->toDateTimeString(),
        ];
    }

    public function index(Request $request)
    {
        $orders = Order::with('user')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn ($o) => $this->transform($o));

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'data'    => $orders
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'address_id'    => 'required|exists:addresses,id',
            'subtotal'      => 'required|numeric|min:0',
            'shipping_fee'  => 'nullable|numeric|min:0',
            'discount'      => 'nullable|numeric|min:0',
        ]);

        $shipping = $request->shipping_fee ?? 0;
        $discount = $request->discount ?? 0;

        $order = Order::create([
            'user_id'        => $request->user()->id,
            'address_id'     => $request->address_id,
            'subtotal'       => $request->subtotal,
            'shipping_fee'   => $shipping,
            'discount'       => $discount,
            'total_amount'   => $request->subtotal + $shipping - $discount,
            'status'         => 'pending',
            'payment_status' => 'unpaid',
            'created_by'     => $request->user()->id,
        ]);

        $order->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data'    => $this->transform($order)
        ], 201);
    }

    public function show(Request $request, $id)
    {
        try {
            $order = Order::with('user')
                ->where('user_id', $request->user()->id)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => $this->transform($order)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);

            $request->validate([
                'status'         => 'in:pending,paid,shipped,completed,cancelled',
                'payment_status' => 'in:unpaid,paid,refunded',
            ]);

            $order->update($request->only([
                'status',
                'payment_status'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data'    => $this->transform($order)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data'    => new \stdClass()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }
}