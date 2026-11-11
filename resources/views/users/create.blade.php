{{-- Formulir pembuatan akun pengguna dan penentuan perannya. --}}
@extends('layouts.app')

@section('title', 'Create User')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2>
        
        Create New User
    </h2>

    <a href="{{ route('users.index') }}" class="btn btn-secondary">
        
        Back
    </a>

</div>

@if ($errors->any())

<div class="alert alert-danger">

    <h6>
        
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

            <div class="mb-3">

                <label class="form-label">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="{{ old('email') }}"
                    placeholder="Enter email for password recovery"
                    maxlength="255">

                <small class="text-muted">
                    Wajib diisi untuk akun admin dan teller.
                </small>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        minlength="12"
                        maxlength="255"
                        data-strong-password
                        autocomplete="new-password"
                        placeholder="Minimum 12 characters"
                        required>

                    @include('components.password-requirements')

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

                    </select>

                </div>

            </div>

            <div class="alert alert-info">
                User baru dibuat dalam status nonaktif. Admin dapat mengaktifkannya dari daftar user.
            </div>

            <hr>

            <button class="btn btn-success">

                

                Save User

            </button>

            <a href="{{ route('users.index') }}"
               class="btn btn-secondary">

                

                Cancel

            </a>

        </form>

    </div>

</div>

@endsection