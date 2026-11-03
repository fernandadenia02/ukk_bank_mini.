@extends('layouts.app')

@section('title', 'New Transaction')

@section('content')

<h2>New Transaction</h2>

<hr>

@if(session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('transactions.store') }}" method="POST">

    @csrf

    <div class="mb-3">

        <label>Customer</label>

        <select
            name="customer_id"
            class="form-select"
            required>

            <option value="">-- Select Customer --</option>

            @foreach($customers as $customer)

                <option value="{{ $customer->id }}">

                    {{ $customer->account_number }} -
                    {{ $customer->name }}

                </option>

            @endforeach

        </select>

    </div>

    <div class="mb-3">

        <label>Transaction Type</label>

        <select
            name="transaction_type"
            class="form-select"
            required>

            <option value="setoran">Deposit</option>
            <option value="penarikan">Withdrawal</option>

        </select>

    </div>

    <div class="mb-3">

        <label>Amount</label>

        <input
            type="number"
            name="amount"
            class="form-control"
            min="1000"
            required>

    </div>

    <button class="btn btn-success">

        Save Transaction

    </button>

    <a href="{{ route('transactions.index') }}"
       class="btn btn-secondary">

        Back

    </a>

</form>

@endsection