<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display user list.
     */
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $users = User::whereIn('role', ['admin', 'teller', 'supervisor'])
        ->when($keyword, function ($query) use ($keyword) {
            $query->where(function ($query) use ($keyword) {
                $query->where('officer_name', 'like', "%{$keyword}%")
                    ->orWhere('username', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('role', 'like', "%{$keyword}%");
            });
        })
        ->orderBy('id', 'desc')
        ->get();

        return view('users.index', compact(
            'users',
            'keyword'
        ));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store new user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'officer_name' => 'required|max:100',
            'username'     => 'required|unique:users,username|unique:customers,username|max:50',
            'email'        => 'nullable|required_if:role,admin|required_if:role,teller|email|max:255|unique:users,email',
            'password'     => ['required', 'string', 'max:255', new StrongPassword],
            'role'         => 'required|in:admin,teller,supervisor',
        ]);

        User::create([

            'officer_name' => $request->officer_name,
            'username'     => $request->username,
            'email'        => $request->email ? strtolower(trim($request->email)) : null,
            'password'     => Hash::make($request->password),
            'role'         => $request->role,
            'status'       => 'nonaktif'

        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);

        return view('users.edit', compact('user'));
    }

    /**
     * Update user.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'officer_name' => 'required|max:100',
            'username'     => 'required|unique:users,username,' . $id
                . '|unique:customers,username',
            'email'        => 'nullable|required_if:role,admin|required_if:role,teller|email|max:255|unique:users,email,' . $id,
            'role'         => 'required|in:admin,teller,supervisor',
            'status'       => 'required|in:aktif,nonaktif',
            'password'     => ['nullable', 'string', 'max:255', new StrongPassword],
        ]);

        $data = [

            'officer_name' => $request->officer_name,
            'username'     => $request->username,
            'email'        => $request->email ? strtolower(trim($request->email)) : null,
            'role'         => $request->role,
            'status'       => $request->status

        ];

        if ($request->filled('password')) {

            $data['password'] = Hash::make($request->password);

        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:aktif,nonaktif',
        ]);

        if ($user->id == $request->user()->id && $validated['status'] === 'nonaktif') {
            return redirect()
                ->route('users.index')
                ->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update([
            'status' => $validated['status'],
        ]);

        if ($validated['status'] === 'nonaktif') {
            $user->tokens()->delete();
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Status user berhasil diperbarui.');
    }
}