@extends('layouts.auth')

@section('title', 'Login')

@section('content')

<div class="container mt-5" style="max-width:450px;">

    <div class="card shadow">

        <div class="card-header text-center bg-primary text-white">
            <h4>Mini Bank Login</h4>
        </div>

        <div class="card-body">

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('login.authenticate') }}" method="POST">

                @csrf

                <div class="mb-3">
                    <label>Username</label>

                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        required>
                </div>

                <div class="mb-3">
                    <label>Password</label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        required>
                </div>

                <button class="btn btn-primary w-100">
                    Login
                </button>

            </form>

        </div>

    </div>

</div>

@endsection