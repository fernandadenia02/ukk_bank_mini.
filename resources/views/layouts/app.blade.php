<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title') | E-Teller Bank Mini</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>

        body{
            background:#f4f6f9;
            overflow-x:hidden;
        }

        .sidebar{

            width:250px;
            height:100vh;
            position:fixed;
            left:0;
            top:0;
            background:#0d6efd;
            color:white;
            overflow-y:auto;

        }

        .sidebar h4{

            padding:20px;
            text-align:center;
            border-bottom:1px solid rgba(255,255,255,.2);

        }

        .sidebar a{

            color:white;
            text-decoration:none;
            display:block;
            padding:14px 20px;
            transition:.2s;

        }

        .sidebar a:hover{

            background:rgba(255,255,255,.15);

        }

        .sidebar a.active{

            background:white;
            color:#0d6efd;
            font-weight:bold;

        }

        .content{

            margin-left:250px;

        }

        .topbar{

            background:white;
            height:70px;
            box-shadow:0 2px 8px rgba(0,0,0,.08);

        }

        .main-content{

            padding:25px;

        }

        .card{

            border:none;
            border-radius:12px;

        }

    </style>

</head>

<body>

<div class="sidebar">

    <h4>

        <i class="bi bi-bank2"></i>

        Bank Mini

    </h4>

    @if(session('role')=='admin')

        <a href="{{ route('admin.dashboard') }}"
           class="{{ request()->routeIs('admin.dashboard')?'active':'' }}">

            <i class="bi bi-speedometer2"></i>

            Dashboard

        </a>

        <a href="{{ route('users.index') }}"
           class="{{ request()->routeIs('users.*')?'active':'' }}">

            <i class="bi bi-people"></i>

            Users

        </a>

        <a href="{{ route('customers.index') }}"
           class="{{ request()->routeIs('customers.*')?'active':'' }}">

            <i class="bi bi-person-vcard"></i>

            Customers

        </a>

        <a href="{{ route('daily-reports.index') }}"
           class="{{ request()->routeIs('daily-reports.*')?'active':'' }}">

            <i class="bi bi-file-earmark-text"></i>

            Daily Reports

        </a>

        <a href="{{ route('accounting-journal.index') }}"
           class="{{ request()->routeIs('accounting-journal.*')?'active':'' }}">

            <i class="bi bi-journal-bookmark"></i>

            Accounting Journal

        </a>

    @elseif(session('role')=='teller')

        <a href="{{ route('teller.dashboard') }}"
           class="{{ request()->routeIs('teller.dashboard')?'active':'' }}">

            <i class="bi bi-speedometer2"></i>

            Dashboard

        </a>

        <a href="{{ route('transactions.index') }}"
           class="{{ request()->routeIs('transactions.*')?'active':'' }}">

            <i class="bi bi-cash-stack"></i>

            Transactions

        </a>

    @elseif(session('role')=='supervisor')

        <a href="{{ route('supervisor.dashboard') }}"
           class="{{ request()->routeIs('supervisor.dashboard')?'active':'' }}">

            <i class="bi bi-speedometer2"></i>

            Dashboard

        </a>

        <a href="{{ route('daily-reports.index') }}"
           class="{{ request()->routeIs('daily-reports.*')?'active':'' }}">

            <i class="bi bi-file-earmark-text"></i>

            Daily Reports

        </a>

    @endif

</div>

<div class="content">

    <nav class="navbar topbar px-4">

        <div>

            <h5 class="mb-0">

                @yield('title')

            </h5>

        </div>

        <div class="d-flex align-items-center">

            <span class="me-4">

                <i class="bi bi-person-circle"></i>

                {{ session('officer_name') }}

                <span class="badge bg-primary">

                    {{ ucfirst(session('role')) }}

                </span>

            </span>

            <form action="{{ route('logout') }}" method="POST">

                @csrf

                <button class="btn btn-danger">

                    <i class="bi bi-box-arrow-right"></i>

                    Logout

                </button>

            </form>

        </div>

    </nav>

    <div class="main-content">

        @if(session('success'))

            <div class="alert alert-success">

                {{ session('success') }}

            </div>

        @endif

        @if(session('error'))

            <div class="alert alert-danger">

                {{ session('error') }}

            </div>

        @endif

        @yield('content')

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>