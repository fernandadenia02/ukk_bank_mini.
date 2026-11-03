@extends('layouts.app')

@section('title', 'Supervisor Dashboard')

@section('content')

<h2 class="mb-4">Supervisor Dashboard</h2>

<div class="row">

    <div class="col-md-6 mb-3">
        <div class="card text-bg-warning shadow">
            <div class="card-body text-center">
                <h5>Total Daily Reports</h5>
                <h2>{{ $totalReports }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="card text-bg-info shadow">
            <div class="card-body text-center">
                <h5>Today's Reports</h5>
                <h2>{{ $todayReports }}</h2>
            </div>
        </div>
    </div>

</div>

@endsection