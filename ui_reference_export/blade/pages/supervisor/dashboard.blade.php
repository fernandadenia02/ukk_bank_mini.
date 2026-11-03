@extends('layouts.app')

@section('title', 'Supervisor Dashboard')

@section('content')

<h2 class="mb-4">
    Supervisor Dashboard
</h2>


<div class="row">

    {{-- TOTAL REPORT --}}
    <div class="col-md-4 mb-3">

        <div class="card text-bg-primary shadow h-100">

            <div class="card-body text-center">

                <h5>
                    Total Daily Reports
                </h5>

                <h2>
                    {{ $totalReports }}
                </h2>

            </div>

        </div>

    </div>


    {{-- TODAY REPORT --}}
    <div class="col-md-4 mb-3">

        <div class="card text-bg-info shadow h-100">

            <div class="card-body text-center">

                <h5>
                    Today's Reports
                </h5>

                <h2>
                    {{ $todayReports }}
                </h2>

            </div>

        </div>

    </div>


    {{-- PENDING --}}
    <div class="col-md-4 mb-3">

        <div class="card text-bg-warning shadow h-100">

            <div class="card-body text-center">

                <h5>
                    Pending Reports
                </h5>

                <h2>
                    {{ $pendingReports }}
                </h2>

            </div>

        </div>

    </div>


    {{-- APPROVED --}}
    <div class="col-md-6 mb-3">

        <div class="card text-bg-success shadow">

            <div class="card-body text-center">

                <h5>
                    Approved Reports
                </h5>

                <h2>
                    {{ $approvedReports }}
                </h2>

            </div>

        </div>

    </div>


    {{-- REJECTED --}}
    <div class="col-md-6 mb-3">

        <div class="card text-bg-danger shadow">

            <div class="card-body text-center">

                <h5>
                    Rejected Reports
                </h5>

                <h2>
                    {{ $rejectedReports }}
                </h2>

            </div>

        </div>

    </div>

</div>


<div class="card shadow mt-4">

    <div class="card-body text-center">

        <h5>
            Daily Report Management
        </h5>

        <p class="text-muted">
            Periksa dan proses laporan harian Teller.
        </p>

        <a
            href="{{ route('daily-reports.index') }}"
            class="btn btn-primary">

            View Daily Reports

        </a>

    </div>

</div>

@endsection