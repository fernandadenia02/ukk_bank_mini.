@extends('layouts.app')

@section('title', 'Users')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2>
        <i class="bi bi-people-fill"></i>
        User Management
    </h2>

    <a href="{{ route('users.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i>
        Add User
    </a>

</div>

@if(session('success'))

<div class="alert alert-success">
    {{ session('success') }}
</div>

@endif

@if(session('error'))

<div class="alert alert-danger">
    {{ session('error') }}
</div>

@endif

<div class="card shadow">

    <div class="card-body">

        <div class="d-flex justify-content-between mb-3">

            <h6>

                Total User

                <span class="badge bg-primary">

                    {{ $users->count() }}

                </span>

            </h6>

        </div>

        <form method="GET"
              action="{{ route('users.index') }}"
              class="row g-2 mb-4">

            <div class="col-md-10">

                <input
                    type="text"
                    name="keyword"
                    class="form-control"
                    placeholder="Search by officer name, username or role..."
                    value="{{ $keyword }}">

            </div>

            <div class="col-md-2">

                <button class="btn btn-primary w-100">

                    <i class="bi bi-search"></i>

                    Search

                </button>

            </div>

        </form>

        <table class="table table-bordered table-hover align-middle">

            <thead class="table-dark">

                <tr>

                    <th width="60">No</th>

                    <th>
                        <i class="bi bi-person-fill"></i>
                        Officer
                    </th>

                    <th>
                        <i class="bi bi-person-badge"></i>
                        Username
                    </th>

                    <th>
                        <i class="bi bi-shield-lock"></i>
                        Role
                    </th>

                    <th>
                        <i class="bi bi-check-circle"></i>
                        Status
                    </th>

                    <th width="190">
                        Action
                    </th>

                </tr>

            </thead>

            <tbody>

            @forelse($users as $user)

                <tr>

                    <td>

                        @if(method_exists($users,'firstItem'))

                            {{ $users->firstItem() + $loop->index }}

                        @else

                            {{ $loop->iteration }}

                        @endif

                    </td>

                    <td>{{ $user->officer_name }}</td>

                    <td>{{ $user->username }}</td>

                    <td>

                        @if($user->role=='admin')

                            <span class="badge bg-danger">

                                Admin

                            </span>

                        @elseif($user->role=='teller')

                            <span class="badge bg-primary">

                                Teller

                            </span>

                        @elseif($user->role=='supervisor')

                            <span class="badge bg-warning text-dark">

                                Supervisor

                            </span>

                        @else

                            <span class="badge bg-success">

                                Customer

                            </span>

                        @endif

                    </td>

                    <td>

                        @if($user->status=='aktif')

                            <span class="badge bg-success">

                                Active

                            </span>

                        @else

                            <span class="badge bg-danger">

                                Inactive

                            </span>

                        @endif

                    </td>

                    <td>

                        <a href="{{ route('users.edit',$user->id) }}"
                           class="btn btn-warning btn-sm">

                            <i class="bi bi-pencil-square"></i>

                            Edit

                        </a>

                        @if($user->id != auth()->id())

                        <form
                            action="{{ route('users.destroy',$user->id) }}"
                            method="POST"
                            class="d-inline"
                            onsubmit="return confirm('Delete this user?')">

                            @csrf
                            @method('DELETE')

                            <button class="btn btn-danger btn-sm">

                                <i class="bi bi-trash"></i>

                                Delete

                            </button>

                        </form>

                        @else

                        <button
                            class="btn btn-secondary btn-sm"
                            disabled>

                            Current User

                        </button>

                        @endif

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="6" class="text-center">

                        No user data available.

                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

        @if(method_exists($users,'links'))

            {{ $users->links() }}

        @endif

    </div>

</div>

@endsection