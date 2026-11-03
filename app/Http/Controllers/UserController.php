<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        $users = User::when($keyword, function ($query) use ($keyword) {

            $query->where('officer_name', 'like', "%{$keyword}%")
                  ->orWhere('username', 'like', "%{$keyword}%")
                  ->orWhere('role', 'like', "%{$keyword}%");

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
            'username'     => 'required|unique:users,username|max:50',
            'password'     => 'required|min:4',
            'role'         => 'required',
            'status'       => 'required'
        ]);

        User::create([

            'officer_name' => $request->officer_name,
            'username'     => $request->username,
            'password'     => Hash::make($request->password),
            'role'         => $request->role,
            'status'       => $request->status

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
            'username'     => 'required|unique:users,username,' . $id,
            'role'         => 'required',
            'status'       => 'required'
        ]);

        $data = [

            'officer_name' => $request->officer_name,
            'username'     => $request->username,
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

    /**
     * Delete user.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if ($user->id == session('id')) {

            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot delete your own account.');

        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}