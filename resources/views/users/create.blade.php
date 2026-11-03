@extends('layouts.app')

@section('title', 'Create User')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2>
        <i class="bi bi-person-plus-fill"></i>
        Create New User
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

    <div class="card-header bg-primary text-white">

        <h5 class="mb-0">

            <i class="bi bi-person-vcard"></i>

            User Information

        </h5>

    </div>

    <div class="card-body">

        <form action="{{ route('users.store') }}" method="POST">

            @csrf

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Officer Name
                    </label>

                    <input
                        type="text"
                        name="officer_name"
                        class="form-control"
                        value="{{ old('officer_name') }}"
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
                        value="{{ old('username') }}"
                        placeholder="Enter username"
                        required>

                </div>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Minimum 4 characters"
                        required>

                    <small class="text-muted">
                        Password minimal 4 karakter.
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

                     <option value="admin">Admin</option>
                    <option value="teller">Teller</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="nasabah">Nasabah</option>

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
                            {{ old('status')=='aktif'?'selected':'' }}>
                            Active
                        </option>

                        <option value="nonaktif"
                            {{ old('status')=='nonaktif'?'selected':'' }}>
                            Inactive
                        </option>

                    </select>

                </div>

            </div>

            <hr>

            <button class="btn btn-success">

                <i class="bi bi-check-circle"></i>

                Save User

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