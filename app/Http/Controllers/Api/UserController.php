<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{

    private function transform(User $user)
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name ?? '',
            'email'      => $user->email ?? '',
            'phone'      => $user->phone ?? '',
            'role'       => $user->role ?? '',

            // Avatar URL
            'avatar'     => $user->avatar ? asset('storage/' . $user->avatar) : '',

            // created_by: admin id or own id
            'created_by' => $user->created_by ?? $user->id,

            // creator: admin or self
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

            'created_at' => $user->created_at?->toDateTimeString() ?? '',
            'updated_at' => $user->updated_at?->toDateTimeString() ?? '',
        ];
    }

    public function index(Request $request)
    {
        $query = User::with('creator');

        // Optional search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Optional filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Optional sort
        switch ($request->sort) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;

            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;

            case 'role':
                $query->orderBy('role', 'asc');
                break;

            default:
                $query->latest();
                break;
        }

        $users = $query->get()->map(fn($u) => $this->transform($u));

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data'    => $users
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'phone'    => 'nullable|string',
            'role'     => 'required|in:customer,admin',
            'avatar'   => 'nullable|image|max:2048', // validate avatar
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'phone'      => $request->phone,
            'role'       => $request->role,
            'created_by' => $request->user()->id ?? null,
            'avatar'     => $avatarPath,
        ]);

        $user->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data'    => $this->transform($user)
        ], 201);
    }

    public function show($id)
    {
        try {
            $user = User::with('creator')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data'    => $this->transform($user)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email,' . $user->id,
                'password' => 'nullable|min:8|confirmed',
                'phone'    => 'nullable|string',
                'role'     => 'required|in:customer,admin',
                'avatar'   => 'nullable|image|max:2048', // validate avatar
            ]);

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar); // delete old avatar
                }
                $user->avatar = $request->file('avatar')->store('avatars', 'public');
            }

            $user->name  = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone ?? '';
            $user->role  = $request->role;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();
            $user->load('creator');

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data'    => $this->transform($user)
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
                'data'    => new \stdClass()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'data'    => new \stdClass()
            ], 404);
        }
    }
}