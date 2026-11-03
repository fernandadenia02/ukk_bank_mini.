@extends('layouts.app')

@section('title', 'Edit User')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2>
        <i class="bi bi-pencil-square"></i>
        Edit User
    </h2>

    <a href="{{ route('users.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i>
        Back
    </a>

</div>

@if ($errors->any())

<div class="alert alert-danger">

    <h6>
        <i class="bi bi-exclamation-triangle-fill"></i>
        Please fix the following errors:
    </h6>

    <ul class="mb-0">

        @foreach ($errors->all() as $error)

            <li>{{ $error }}</li>

        @endforeach

    </ul>

</div>

@endif

<div class="card shadow">

    <div class="card-header bg-warning">

        <h5 class="mb-0 text-dark">

            <i class="bi bi-person-gear"></i>

            Edit User Information

        </h5>

    </div>

    <div class="card-body">

        <form action="{{ route('users.update',$user->id) }}" method="POST">

            @csrf
            @method('PUT')

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Officer Name
                    </label>

                    <input
                        type="text"
                        name="officer_name"
                        class="form-control"
                        value="{{ old('officer_name',$user->officer_name) }}"
                        placeholder="Enter officer name"
                        required>

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        value="{{ old('username',$user->username) }}"
                        placeholder="Enter username"
                        required>

                </div>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        New Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Leave blank if unchanged">

                    <small class="text-muted">

                        Leave this field empty if you don't want to change the password.

                    </small>

                </div>

                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        Role
                    </label>

                    <select
                        name="role"
                        class="form-select"
                        required>

                        <option value="admin"
                            {{ old('role',$user->role)=='admin' ? 'selected' : '' }}>
                            Admin
                        </option>

                        <option value="teller"
                            {{ old('role',$user->role)=='teller' ? 'selected' : '' }}>
                            Teller
                        </option>

                        <option value="supervisor"
                            {{ old('role',$user->role)=='supervisor' ? 'selected' : '' }}>
                            Supervisor
                        </option>

                        <option value="nasabah"
                            {{ old('role', $user->role) == 'nasabah' ? 'selected' : '' }}>
                            Nasabah
                        </option>

                    </select>

                </div>

                <div class="col-md-3 mb-3">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                        required>

                        <option value="aktif"
                            {{ old('status',$user->status)=='aktif' ? 'selected' : '' }}>
                            Active
                        </option>

                        <option value="nonaktif"
                            {{ old('status',$user->status)=='nonaktif' ? 'selected' : '' }}>
                            Inactive
                        </option>

                    </select>

                </div>

            </div>

            <hr>

            <button class="btn btn-warning">

                <i class="bi bi-check-circle"></i>

                Update User

            </button>

            <a href="{{ route('users.index') }}"
               class="btn btn-secondary">

                <i class="bi bi-x-circle"></i>

                Cancel

            </a>

        </form>

    </div>

</div>

@endsection