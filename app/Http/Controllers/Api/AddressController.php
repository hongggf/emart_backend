<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddressController extends Controller
{
    // ===================== GET ALL ADDRESSES OF LOGGED-IN USER =====================
    public function index(Request $request)
    {
        $addresses = Address::with(['user:id,name,email', 'creator:id,name'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Addresses retrieved successfully',
            'data'    => $addresses
        ]);
    }

    // ===================== CREATE ADDRESS =====================
    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'required|string|max:30',
            'province'  => 'required|string|max:255',
            'district'  => 'required|string|max:255',
            'street'    => 'required|string|max:255',
            'is_default'=> 'boolean',
        ]);

        // If set as default → unset other defaults for this user
        if ($request->is_default) {
            Address::where('user_id', $request->user()->id)
                   ->update(['is_default' => false]);
        }

        $address = Address::create([
            'user_id'    => $request->user()->id,
            'full_name'  => $request->full_name,
            'phone'      => $request->phone,
            'province'   => $request->province,
            'district'   => $request->district,
            'street'     => $request->street,
            'is_default' => $request->is_default ?? false,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address created successfully',
            'data'    => $address
        ], 201);
    }

    // ===================== SHOW SINGLE ADDRESS =====================
    public function show()
    {
        try {
            $address = Address::with(['user:id,name,email', 'creator:id,name'])
                ->where('user_id', auth()->id())   // only own user
                ->where('is_default', true)       // only default address
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Default address retrieved successfully',
                'data'    => $address
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Default address not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    // ===================== UPDATE ADDRESS =====================
    public function update(Request $request, $id)
    {
        try {
            $address = Address::where('user_id', $request->user()->id)
                ->findOrFail($id);

            $request->validate([
                'full_name'  => 'required|string|max:255',
                'phone'      => 'required|string|max:30',
                'province'   => 'required|string|max:255',
                'district'   => 'required|string|max:255',
                'street'     => 'required|string|max:255',
                'is_default' => 'boolean',
            ]);

            // If user sets this address as default → unset other defaults
            if ($request->is_default) {
                Address::where('user_id', $request->user()->id)
                    ->where('id', '<>', $id) // exclude current address
                    ->update(['is_default' => false]);
            }

            $address->update($request->only([
                'full_name',
                'phone',
                'province',
                'district',
                'street',
                'is_default',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully',
                'data'    => $address
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    // ===================== DELETE ADDRESS =====================
    public function destroy($id)
    {
        try {
            $address = Address::where('user_id', auth()->id())->findOrFail($id);
            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully',
                'data'    => new \stdClass()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }
}