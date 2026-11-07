{{-- Formulir untuk memperbarui data profil dan rekening nasabah. --}}
@extends('layouts.app')

@section('title','Edit Customer')

@section('content')

<h2>Edit Customer</h2>

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

<form action="{{ route('customers.update',$customer->id) }}" method="POST">

@csrf
@method('PUT')

<div class="mb-3">

<label>Account Number</label>

<input
type="text"
class="form-control"
value="{{ $customer->account_number }}"
readonly>

</div>

<div class="mb-3">

<label>NIS</label>

<input
type="text"
name="nis"
class="form-control"
value="{{ old('nis',$customer->nis) }}"
required>

</div>

<div class="mb-3">

<label>Name</label>

<input
type="text"
name="name"
class="form-control"
value="{{ old('name',$customer->name) }}"
required>

</div>

<div class="mb-3">

<label for="email">Email</label>

<input
type="email"
id="email"
name="email"
class="form-control"
value="{{ old('email', $customer->email) }}"
maxlength="255"
autocomplete="email"
required>

<small class="text-muted">
Email ini digunakan untuk menerima kode OTP saat lupa password.
</small>

</div>

<div class="mb-3">

<label>Class</label>

<input
type="text"
name="class"
class="form-control"
value="{{ old('class',$customer->class) }}"
required>

</div>

<div class="mb-3">

<label>Location</label>

<textarea
name="location"
class="form-control"
required>{{ old('location',$customer->location) }}</textarea>

</div>

<div class="mb-3">

<label>Telephone</label>

<input
type="text"
name="telephone_number"
class="form-control"
value="{{ old('telephone_number',$customer->telephone_number) }}"
required>

</div>

<button class="btn btn-primary">

Update

</button>

<a href="{{ route('customers.index') }}"
class="btn btn-secondary">

Back

</a>

</form>

</div>

</div>

@endsection