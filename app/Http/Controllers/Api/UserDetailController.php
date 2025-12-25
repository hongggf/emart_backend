<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserDetailController extends Controller
{
    
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => $this->transform($user),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'     => 'nullable|string|max:255',
            'email'    => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'phone'    => 'nullable|string|max:20',
            'avatar'   => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // -------------------- BASIC INFO --------------------
        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->filled('email')) {
            $user->email = $request->email;
        }

        if ($request->filled('phone')) {
            $user->phone = $request->phone;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // -------------------- AVATAR --------------------
        if ($request->hasFile('avatar')) {

            // delete old avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $this->transform($user),
        ]);
    }

    private function transform(User $user)
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name ?? '',
            'email'      => $user->email ?? '',
            'phone'      => $user->phone ?? '',
            'role'       => $user->role ?? '',

            'avatar'     => $user->avatar
                ? asset('storage/' . $user->avatar)
                : '',

            'created_by' => $user->created_by ?? $user->id,

            'creator'    => $user->creator
                ? [
                    'id'    => $user->creator->id,
                    'name'  => $user->creator->name ?? '',
                    'email' => $user->creator->email ?? '',
                ]
                : [
                    'id'    => $user->id,
                    'name'  => $user->name ?? '',
                    'email' => $user->email ?? '',
                ],

            'created_at' => $user->created_at?->toDateTimeString(),
            'updated_at' => $user->updated_at?->toDateTimeString(),
        ];
    }
}