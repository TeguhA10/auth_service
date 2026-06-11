<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * GET /users
     */
    public function index(): JsonResponse
    {
        $users = User::orderBy('id')->get();

        return response()->json([
            'message' => 'Success',
            'data' => $users,
        ]);
    }

    /**
     * GET /users/{id}
     */
    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $user,
        ]);
    }

    /**
     * POST /users
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => [
                'required',
                Rule::in([
                    'superadmin',
                    'admin_hrd',
                    'admin_cabang',
                    'karyawan',
                    'admin_purchasing',
                    'staff_purchasing',
                ]),
            ],
            'branch_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $user,
        ], 201);
    }

    /**
     * PUT /users/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'email' => [
                'sometimes',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => [
                'sometimes',
                Rule::in([
                    'superadmin',
                    'admin_hrd',
                    'admin_cabang',
                    'karyawan',
                    'admin_purchasing',
                    'staff_purchasing',
                ]),
            ],
            'branch_id' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('email', $validated)) {
            $user->email = strtolower($validated['email']);
        }

        if (array_key_exists('password', $validated)) {
            $user->password = Hash::make($validated['password']);
        }

        if (array_key_exists('role', $validated)) {
            $user->role = $validated['role'];
        }

        if (array_key_exists('branch_id', $validated)) {
            $user->branch_id = $validated['branch_id'];
        }

        if (array_key_exists('is_active', $validated)) {
            $user->is_active = $validated['is_active'];
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $user,
        ]);
    }

    /**
     * DELETE /users/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}