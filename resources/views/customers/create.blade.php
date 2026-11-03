@extends('layouts.app')

@section('title', 'Create Customer')

@section('content')

<h2>Create Customer</h2>

<hr>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card shadow">

    <div class="card-body">

        <form action="{{ route('customers.store') }}" method="POST">

            @csrf

            <div class="mb-3">
                <label>Account Number</label>

                <input
                    type="text"
                    name="account_number"
                    class="form-control"
                    value="{{ $accountNumber }}"
                    readonly>
            </div>

            <div class="mb-3">
                <label>NIS</label>

                <input
                    type="text"
                    name="nis"
                    class="form-control"
                    required>
            </div>

            <div class="mb-3">
                <label>Customer Name</label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    required>
            </div>

            <div class="mb-3">
                <label>Class</label>

                <input
                    type="text"
                    name="class"
                    class="form-control"
                    required>
            </div>

            <div class="mb-3">
                <label>Location</label>

                <textarea
                    name="location"
                    class="form-control"
                    rows="3"
                    required></textarea>
            </div>

            <div class="mb-3">
                <label>Telephone Number</label>

                <input
                    type="text"
                    name="telephone_number"
                    class="form-control"
                    required>
            </div>

            <div class="mb-3">
                <label>PIN</label>

                <input
                    type="password"
                    name="pin"
                    class="form-control"
                    maxlength="6"
                    required>
            </div>

            <button class="btn btn-success">
                Save Customer
            </button>

            <a href="{{ route('customers.index') }}"
               class="btn btn-secondary">
                Back
            </a>

        </form>

    </div>

</div>

@endsection