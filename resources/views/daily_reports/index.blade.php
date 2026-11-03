@extends('layouts.app')

@section('title','Daily Reports')

@section('content')

<h2>Daily Reports</h2>

<hr>

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<table class="table table-bordered table-hover">

    <thead class="table-dark">

        <tr>

            <th>No</th>
            <th>Date</th>
            <th>Teller</th>
            <th>Deposit</th>
            <th>Withdrawal</th>
            <th>Ending Balance</th>
            <th>Status</th>
            <th>Action</th>

        </tr>

    </thead>

    <tbody>

    @foreach($reports as $report)

    <tr>

        <td>{{ $loop->iteration }}</td>

        <td>{{ $report->date }}</td>

        <td>{{ $report->teller->officer_name ?? '-' }}</td>

        <td>Rp {{ number_format($report->total_deposit,0,',','.') }}</td>

        <td>Rp {{ number_format($report->total_withdrawals,0,',','.') }}</td>

        <td>Rp {{ number_format($report->ending_balance,0,',','.') }}</td>

        <td>

            @if($report->status=='pending')
                <span class="badge bg-warning">Pending</span>
            @elseif($report->status=='approved')
                <span class="badge bg-success">Approved</span>
            @else
                <span class="badge bg-danger">Rejected</span>
            @endif

        </td>

        <td>

            @if(session('role')=='admin')

                @if($report->status=='pending')

                    <form action="{{ route('daily-reports.approve',$report->id) }}" method="POST" class="d-inline">

                        @csrf

                        <button class="btn btn-success btn-sm">
                            Approve
                        </button>

                    </form>

                    <form action="{{ route('daily-reports.reject',$report->id) }}" method="POST" class="d-inline">

                        @csrf

                        <button class="btn btn-danger btn-sm">
                            Reject
                        </button>

                    </form>

                @else

                    -

                @endif

            @else

                <span class="text-muted">View Only</span>

            @endif

        </td>

    </tr>

    @endforeach

    </tbody>

</table>

@endsection