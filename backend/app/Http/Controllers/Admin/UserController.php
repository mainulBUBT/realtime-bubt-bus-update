<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query()->withCount('trips');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:admin,driver,student',
            'phone'    => 'nullable|string|max:20',
            'approval_status' => 'nullable|in:pending,approved',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['approval_status'] = $validated['role'] === 'driver'
            ? ($validated['approval_status'] ?? 'approved')
            : 'approved';

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('toastr', [['type' => 'success', 'message' => 'User created successfully.']]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role'     => 'required|in:admin,driver,student',
            'phone'    => 'nullable|string|max:20',
            'approval_status' => 'nullable|in:pending,approved',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $validated['approval_status'] = $validated['role'] === 'driver'
            ? ($validated['approval_status'] ?? ($user->approval_status ?? 'approved'))
            : 'approved';

        $user->update($validated);

        if ($user->role === 'driver' && $user->isPendingApproval()) {
            $user->tokens()->delete();
        }

        return redirect()->route('admin.users.index')
            ->with('toastr', [['type' => 'success', 'message' => 'User updated successfully.']]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('toastr', [['type' => 'error', 'message' => 'You cannot delete your own account.']]);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('toastr', [['type' => 'success', 'message' => 'User deleted successfully.']]);
    }

    /**
     * Update approval status for a driver.
     */
    public function updateApproval(Request $request, User $user)
    {
        $validated = $request->validate([
            'approval_status' => 'required|in:pending,approved',
        ]);

        if ($user->role !== 'driver') {
            return redirect()->route('admin.users.index')
                ->with('toastr', [['type' => 'error', 'message' => 'Only driver accounts can be approved or revoked.']]);
        }

        $user->update([
            'approval_status' => $validated['approval_status'],
        ]);

        if ($user->isPendingApproval()) {
            $user->tokens()->delete();
        }

        $message = $user->isApproved()
            ? 'Driver approved successfully.'
            : 'Driver approval revoked successfully.';

        return redirect()->route('admin.users.index')
            ->with('toastr', [['type' => 'success', 'message' => $message]]);
    }
}
