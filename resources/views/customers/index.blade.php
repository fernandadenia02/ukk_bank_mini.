@extends('layouts.app')

@section('title','Customers')

@section('content')

<h2>Customer Data</h2>

<hr>

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">

    <a href="{{ route('customers.create') }}"
       class="btn btn-primary">
        Add Customer
    </a>

    <form action="{{ route('customers.index') }}"
          method="GET"
          class="d-flex">

        <input
            type="text"
            name="keyword"
            class="form-control me-2"
            placeholder="Search..."
            value="{{ request('keyword') }}">

        <button class="btn btn-primary me-2">
            Search
        </button>

        <a href="{{ route('customers.index') }}"
           class="btn btn-secondary">
            Reset
        </a>

    </form>

</div>

<table class="table table-bordered table-hover">

    <thead class="table-dark">

        <tr>

            <th width="60">No</th>
            <th>Account Number</th>
            <th>NIS</th>
            <th>Name</th>
            <th>Class</th>
            <th>Balance</th>
            <th width="100">QR Code</th>
            <th width="170">Action</th>

        </tr>

    </thead>

    <tbody>

    @forelse($customers as $customer)

        <tr>

            <td>
                {{ ($customers->currentPage() - 1) * $customers->perPage() + $loop->iteration }}
            </td>

            <td>{{ $customer->account_number }}</td>

            <td>{{ $customer->nis }}</td>

            <td>{{ $customer->name }}</td>

            <td>{{ $customer->class }}</td>

            <td>
                Rp {{ number_format($customer->balance,0,',','.') }}
            </td>

            <td class="text-center">

                @if($customer->qrcode)

                    <img
                        src="{{ asset('qrcode/'.$customer->qrcode) }}"
                        width="70">

                @else

                    -

                @endif

            </td>

            <td>

                <a href="{{ route('customers.edit',$customer->id) }}"
                   class="btn btn-warning btn-sm">

                    Edit

                </a>

                <form
                    action="{{ route('customers.destroy',$customer->id) }}"
                    method="POST"
                    class="d-inline">

                    @csrf
                    @method('DELETE')

                    <button
                        class="btn btn-danger btn-sm"
                        onclick="return confirm('Delete this customer?')">

                        Delete

                    </button>

                </form>

            </td>

        </tr>

    @empty

        <tr>

            <td colspan="8" class="text-center">

                No customer data available.

            </td>

        </tr>

    @endforelse

    </tbody>

</table>

<div class="d-flex justify-content-center">

    {{ $customers->links() }}

</div>

@endsection