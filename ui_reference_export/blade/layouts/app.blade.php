<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>@yield('title') | E-Teller Bank Mini</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>

        /* =====================================
           GLOBAL
        ===================================== */

        body {
            background: #f4f6f9;
            overflow-x: hidden;
            margin: 0;
        }


        /* =====================================
           SIDEBAR
        ===================================== */

        .sidebar {

            width: 250px;

            height: 100vh;

            position: fixed;

            left: 0;
            top: 0;

            background: #0d6efd;

            color: white;

            overflow-y: auto;

            z-index: 1050;

            transition: transform .3s ease;

        }


        .sidebar h4 {

            padding: 20px;

            margin: 0;

            text-align: center;

            border-bottom:
                1px solid
                rgba(255,255,255,.2);

        }


        .sidebar a {

            color: white;

            text-decoration: none;

            display: block;

            padding: 14px 20px;

            transition: .2s;

        }


        .sidebar a:hover {

            background:
                rgba(255,255,255,.15);

        }


        .sidebar a.active {

            background: white;

            color: #0d6efd;

            font-weight: bold;

        }


        /* =====================================
           SIDEBAR CLOSE BUTTON
        ===================================== */

        .sidebar-close {

            display: none;

            position: absolute;

            top: 15px;

            right: 15px;

            border: none;

            background: transparent;

            color: white;

            font-size: 24px;

        }


        /* =====================================
           CONTENT
        ===================================== */

        .content {

            margin-left: 250px;

            min-height: 100vh;

            transition:
                margin-left .3s ease;

        }


        /* =====================================
           TOPBAR
        ===================================== */

        .topbar {

            background: white;

            min-height: 70px;

            box-shadow:
                0 2px 8px
                rgba(0,0,0,.08);

            display: flex;

            align-items: center;

        }


        .main-content {

            padding: 25px;

        }


        /* =====================================
           MENU BUTTON
        ===================================== */

        .menu-button {

            display: none;

            border: none;

            background: transparent;

            font-size: 25px;

            margin-right: 15px;

            color: #212529;

        }


        /* =====================================
           OVERLAY
        ===================================== */

        .sidebar-overlay {

            display: none;

            position: fixed;

            inset: 0;

            background:
                rgba(0,0,0,.45);

            z-index: 1040;

        }


        /* =====================================
           CARD
        ===================================== */

        .card {

            border: none;

            border-radius: 12px;

        }


        /* =====================================
           TABLE
        ===================================== */

        .table-responsive {

            overflow-x: auto;

        }


        /* =====================================
           MOBILE
        ===================================== */

        @media (max-width: 767.98px) {


            /* SIDEBAR TERTUTUP */

            .sidebar {

                transform:
                    translateX(-100%);

            }


            /* SIDEBAR TERBUKA */

            .sidebar.show {

                transform:
                    translateX(0);

            }


            /* CONTENT FULL WIDTH */

            .content {

                margin-left: 0;

                width: 100%;

            }


            /* MENU BUTTON */

            .menu-button {

                display: inline-block;

            }


            /* CLOSE BUTTON */

            .sidebar-close {

                display: block;

            }


            /* OVERLAY */

            .sidebar-overlay.show {

                display: block;

            }


            /* TOPBAR */

            .topbar {

                padding-left: 15px !important;

                padding-right: 15px !important;

                min-height: 65px;

            }


            .topbar-title {

                font-size: 1rem;

                white-space: nowrap;

                overflow: hidden;

                text-overflow: ellipsis;

            }


            /* USER AREA */

            .user-area {

                gap: 8px;

            }


            .user-info {

                display: none;

            }


            /* LOGOUT */

            .logout-button {

                padding: 8px 10px;

            }


            .logout-button span {

                display: none;

            }


            /* CONTENT */

            .main-content {

                padding: 15px;

            }


            /* H2 */

            .main-content h2 {

                font-size: 1.5rem;

            }

        }


        /* =====================================
           VERY SMALL PHONE
        ===================================== */

        @media (max-width: 400px) {

            .main-content {

                padding: 12px;

            }


            .topbar-title {

                max-width: 130px;

            }


            .menu-button {

                font-size: 22px;

                margin-right: 8px;

            }

        }

    </style>

    <link
        href="{{ asset('css/bank-mini-ui.css') }}"
        rel="stylesheet"
    >

</head>


<body>


{{-- ========================================= --}}
{{-- SIDEBAR --}}
{{-- ========================================= --}}

<div
    class="sidebar"
    id="sidebar"
>


    {{-- SIDEBAR HEADER --}}

    <h4>

        <i class="bi bi-bank2"></i>

        Bank Mini

    </h4>


    {{-- CLOSE BUTTON MOBILE --}}

    <button
        type="button"
        class="sidebar-close"
        id="sidebarClose"
        aria-label="Tutup navigasi"
        aria-controls="sidebar"
    >

        <i class="bi bi-x-lg"></i>

    </button>


    {{-- ========================================= --}}
    {{-- ADMIN --}}
    {{-- ========================================= --}}

    @if(session('role') == 'admin')


        <a
            href="{{ route('admin.dashboard') }}"
            class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
        >

            <i class="bi bi-speedometer2"></i>

            Dashboard

        </a>


        <a
            href="{{ route('users.index') }}"
            class="{{ request()->routeIs('users.*') ? 'active' : '' }}"
        >

            <i class="bi bi-people"></i>

            Users

        </a>


        <a
            href="{{ route('customers.index') }}"
            class="{{ request()->routeIs('customers.*') ? 'active' : '' }}"
        >

            <i class="bi bi-person-vcard"></i>

            Customers

        </a>


        <a
            href="{{ route('members.index') }}"
            class="{{ request()->routeIs('members.promotion', 'members.promote') ? '' : (request()->routeIs('members.*') ? 'active' : '') }}"
        >

            <i class="bi bi-person-lines-fill"></i>

            Anggota

        </a>

        <a
            href="{{ route('members.promotion') }}"
            class="{{ request()->routeIs('members.promotion', 'members.promote') ? 'active' : '' }}"
        >

            <i class="bi bi-arrow-up-circle"></i>

            Naik Kelas

        </a>


        <a
            href="{{ route('daily-reports.index') }}"
            class="{{ request()->routeIs('daily-reports.*') ? 'active' : '' }}"
        >

            <i class="bi bi-file-earmark-text"></i>

            Daily Reports

        </a>

        <a
            href="{{ route('monthly-reports.index') }}"
            class="{{ request()->routeIs('monthly-reports.*') ? 'active' : '' }}"
        >

            <i class="bi bi-calendar-month"></i>

            Laporan Bulanan

        </a>


        <a
            href="{{ route('accounting-journal.index') }}"
            class="{{ request()->routeIs('accounting-journal.*') ? 'active' : '' }}"
        >

            <i class="bi bi-journal-bookmark"></i>

            Accounting Journal

        </a>


    {{-- ========================================= --}}
    {{-- TELLER --}}
    {{-- ========================================= --}}

    @elseif(session('role') == 'teller')


        <a
            href="{{ route('teller.dashboard') }}"
            class="{{ request()->routeIs('teller.dashboard') ? 'active' : '' }}"
        >

            <i class="bi bi-speedometer2"></i>

            Dashboard

        </a>


        <a
            href="{{ route('transactions.index') }}"
            class="{{ request()->routeIs('transactions.*') ? 'active' : '' }}"
        >

            <i class="bi bi-cash-stack"></i>

            Transactions

        </a>

        <a
            href="{{ route('teller.members.index') }}"
            class="{{ request()->routeIs('teller.members.*') ? 'active' : '' }}"
        >

            <i class="bi bi-person-vcard"></i>

            Buka Rekening

        </a>

    {{-- ========================================= --}}
    {{-- SUPERVISOR --}}
    {{-- ========================================= --}}

    @elseif(session('role') == 'supervisor')


        <a
            href="{{ route('supervisor.dashboard') }}"
            class="{{ request()->routeIs('supervisor.dashboard') ? 'active' : '' }}"
        >

            <i class="bi bi-speedometer2"></i>

            Dashboard

        </a>


        <a
            href="{{ route('daily-reports.index') }}"
            class="{{ request()->routeIs('daily-reports.*') ? 'active' : '' }}"
        >

            <i class="bi bi-file-earmark-text"></i>

            Daily Reports

        </a>


    {{-- ========================================= --}}
    {{-- CUSTOMER --}}
    {{-- ========================================= --}}

    @elseif(session('role') == 'customer')


        <a
            href="{{ route('customer.dashboard') }}"
            class="{{ request()->routeIs('customer.dashboard') ? 'active' : '' }}"
        >

            <i class="bi bi-speedometer2"></i>

            Dashboard

        </a>


    @endif


</div>



{{-- ========================================= --}}
{{-- OVERLAY MOBILE --}}
{{-- ========================================= --}}

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
    aria-hidden="true"
></div>



{{-- ========================================= --}}
{{-- MAIN CONTENT --}}
{{-- ========================================= --}}

<div class="content">


    {{-- ========================================= --}}
    {{-- TOPBAR --}}
    {{-- ========================================= --}}

    <nav class="navbar topbar px-4">


        <div class="d-flex align-items-center">


            {{-- MENU BUTTON MOBILE --}}

            <button
                type="button"
                class="menu-button"
                id="sidebarToggle"
                aria-label="Buka navigasi"
                aria-controls="sidebar"
                aria-expanded="false"
            >

                <i class="bi bi-list"></i>

            </button>


            {{-- PAGE TITLE --}}

            <h5 class="mb-0 topbar-title">

                @yield('title')

            </h5>


        </div>



        {{-- ========================================= --}}
        {{-- USER AREA --}}
        {{-- ========================================= --}}

        <div
            class="d-flex align-items-center user-area ms-auto"
        >


            {{-- USER INFO --}}

            <div class="user-info text-end me-3">

                <div>

                    <i class="bi bi-person-circle"></i>

                    {{ session('officer_name') }}

                </div>


                <span class="badge bg-primary">

                    {{ ucfirst(session('role')) }}

                </span>

            </div>


            {{-- LOGOUT --}}

            <form
                action="{{ route('logout') }}"
                method="POST"
            >

                @csrf

                <button
                    type="submit"
                    class="btn btn-danger logout-button"
                >

                    <i class="bi bi-box-arrow-right"></i>

                    <span>
                        Logout
                    </span>

                </button>

            </form>


        </div>


    </nav>



    {{-- ========================================= --}}
    {{-- PAGE CONTENT --}}
    {{-- ========================================= --}}

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



{{-- ========================================= --}}
{{-- JAVASCRIPT --}}
{{-- ========================================= --}}

<script>

document.addEventListener('DOMContentLoaded', function () {


    const sidebar =
        document.getElementById('sidebar');


    const toggle =
        document.getElementById('sidebarToggle');


    const close =
        document.getElementById('sidebarClose');


    const overlay =
        document.getElementById('sidebarOverlay');


    /* =====================================
       OPEN SIDEBAR
    ===================================== */

    if (!sidebar || !toggle || !close || !overlay) {
        return;
    }

    toggle.addEventListener('click', function () {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        toggle.setAttribute('aria-expanded', 'true');
    });


    /* =====================================
       CLOSE SIDEBAR
    ===================================== */

    function closeSidebar() {

        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        toggle.setAttribute('aria-expanded', 'false');
    }


    close.addEventListener(
        'click',
        closeSidebar
    );


    overlay.addEventListener(
        'click',
        closeSidebar
    );


    /* =====================================
       CLOSE AFTER CLICK MENU
       KHUSUS MOBILE
    ===================================== */

    sidebar
        .querySelectorAll('a')
        .forEach(function (link) {

            link.addEventListener(
                'click',
                function () {

                    if (
                        window.innerWidth <= 767
                    ) {

                        closeSidebar();

                    }

                }
            );

        });


});

</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>