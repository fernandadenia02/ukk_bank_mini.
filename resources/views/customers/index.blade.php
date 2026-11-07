{{-- Daftar nasabah aktif dan belum aktif beserta aksi pengelolaan rekening. --}}
@extends('layouts.app')

@section('title','Customers')

@section('content')

<h2>Customer Data</h2>

<hr>

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
            <th>Status</th>
            <th width="100">QR Code</th>
            <th width="200">Action</th>

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

            <td><span class="badge bg-success">Active</span></td>

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
                    action="{{ route('customers.status',$customer->id) }}"
                    method="POST"
                    class="d-inline">

                    @csrf
                    @method('PATCH')

                    <input type="hidden" name="status" value="nonaktif">

                    <button class="btn btn-secondary btn-sm"
                        onclick="return confirm('Nonaktifkan customer ini?')">
                        Deactivate

                    </button>

                </form>

            </td>

        </tr>

    @empty

        <tr>

            <td colspan="9" class="text-center">

                No customer data available.

            </td>

        </tr>

    @endforelse

    </tbody>

</table>

<div class="d-flex justify-content-center">

    {{ $customers->links() }}

</div>

<hr class="my-5">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Customer Belum Aktif</h3>
    <span class="badge bg-danger text-white">{{ $inactiveCustomers->total() }}</span>
</div>

<p class="text-muted">
    Admin dapat mengaktifkan customer jika saldo minimal Rp 10.000. Customer dengan saldo di bawah batas tersebut perlu menabung terlebih dahulu.
</p>

<table class="table table-bordered table-hover">
    <thead class="table-dark">
        <tr>
            <th>No</th>
            <th>Account Number</th>
            <th>NIS</th>
            <th>Name</th>
            <th>Class</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    @forelse($inactiveCustomers as $customer)
        <tr>
            <td>{{ ($inactiveCustomers->currentPage() - 1) * $inactiveCustomers->perPage() + $loop->iteration }}</td>
            <td>{{ $customer->account_number }}</td>
            <td>{{ $customer->nis }}</td>
            <td>{{ $customer->name }}</td>
            <td>{{ $customer->class }}</td>
            <td>Rp {{ number_format($customer->balance,0,',','.') }}</td>
            <td><span class="badge bg-secondary">Inactive</span></td>
            <td>
                <a href="{{ route('customers.edit',$customer->id) }}" class="btn btn-warning btn-sm">
                    Edit
                </a>
                @if((float) $customer->balance >= 10000)
                    <form action="{{ route('customers.status',$customer->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="aktif">
                        <button class="btn btn-success btn-sm"
                            onclick="return confirm('Aktifkan customer ini?')">
                            Activate
                        </button>
                    </form>
                @else
                    <button class="btn btn-success btn-sm" disabled
                        title="Saldo harus minimal Rp 10.000 untuk aktivasi">
                        Activate
                    </button>
                    <span class="small text-muted">Saldo harus minimal Rp 10.000</span>
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="text-center">Tidak ada customer nonaktif.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="d-flex justify-content-center">
    {{ $inactiveCustomers->links() }}
</div>

@endsection