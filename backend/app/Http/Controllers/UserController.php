<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::where('role', 'user')->with('leaveBalances.leaveType')->get();
        return response()->json(['data' => $users]);
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Cek batasan maksimal 2 user
        $userCount = User::where('role', 'user')->count();
        if ($userCount >= 2) {
            return response()->json([
                'message' => 'Gagal membuat user. Maksimal hanya diperbolehkan 2 user'
            ], 400); // 
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ], [
            'email.unique' => 'Email ini sudah digunakan oleh user lain',
            'email.email' => 'Format email tidak valid',
            'password.min' => 'Password minimal harus 8 karakter'
        ]); // 

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ]); 

        // Auto-assign leave balance
        $leaveTypes = LeaveType::all();
        $currentYear = date('Y');

        foreach ($leaveTypes as $type) {
            LeaveBalance::create([
                'user_id' => $user->id,
                'leave_type_id' => $type->id,
                'year' => $currentYear,
                'total_quota' => $type->default_quota,
                'used' => 0,
            ]);
        } // 

        return response()->json([
            'message' => 'User berhasil dibuat dan kuota cuti otomatis terisi',
            'data' => $user
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|nullable|string|min:8',
        ], [
            'email.unique' => 'Email ini sudah digunakan oleh user lain',
            'email.email' => 'Format email tidak valid'
        ]); 

        if ($request->has('name')) $user->name = $validated['name'];
        if ($request->has('email')) $user->email = $validated['email'];
        if ($request->filled('password')) $user->password = Hash::make($validated['password']);

        $user->save();

        return response()->json([
            'message' => 'Data user berhasil diperbarui',
            'data' => $user
        ]);
    }
}