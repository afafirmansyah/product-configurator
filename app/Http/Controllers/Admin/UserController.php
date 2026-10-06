<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('company', 'like', "%{$request->search}%");
            }))
            ->when($request->role, fn($q) => $q->whereHas('roles', fn($q) => $q->where('name', $request->role)))
            ->when($request->status !== null && $request->status !== '', fn($q) => $q->where('is_active', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'company'  => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:50',
            'country'  => 'nullable|string|max:100',
            'role'     => 'required|exists:roles,name',
            'is_active'=> 'boolean',
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'company'   => $data['company'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'country'   => $data['country'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->assignRole($data['role']);

        app(AuditService::class)->log('created', $user, [], $user->toArray());

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load('roles');
        $recentActivity = \App\Models\AuditLog::where('user_id', $user->id)->latest()->limit(10)->get();
        return view('admin.users.show', compact('user', 'recentActivity'));
    }

    public function edit(User $user)
    {
        $user->load('roles');
        $roles = Role::orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'company'  => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:50',
            'country'  => 'nullable|string|max:100',
            'role'     => 'required|exists:roles,name',
            'is_active'=> 'boolean',
        ]);

        $old = $user->toArray();

        $user->update([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'company'   => $data['company'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'country'   => $data['country'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->syncRoles([$data['role']]);

        app(AuditService::class)->log('updated', $user, $old, $user->fresh()->toArray());

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        app(AuditService::class)->log('deleted', $user, $user->toArray(), []);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);
        app(AuditService::class)->log($user->is_active ? 'activated' : 'deactivated', $user);

        return back()->with('success', 'User status updated.');
    }

    public function resetPassword(User $user)
    {
        $password = Str::random(12);
        $user->update(['password' => Hash::make($password)]);
        app(AuditService::class)->log('password_reset', $user);

        return back()->with('success', "Password reset. Temporary password: {$password}");
    }
}
