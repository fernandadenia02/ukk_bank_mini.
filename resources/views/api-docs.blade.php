<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bank Mini API Documentation</title>

    <style>
        /* Layout and presentation styles for the API documentation page. */
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f6f7f9;
            color: #202124;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: #17191c;
            color: #fff;
            padding: 28px 20px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            overflow-y: auto;
        }

        .brand {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .version {
            color: #9ca3af;
            font-size: 13px;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: #cfd3d8;
            text-decoration: none;
            padding: 10px 12px;
            border-radius: 7px;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .sidebar a:hover {
            background: #292d32;
            color: #fff;
        }

        .content {
            margin-left: 260px;
            width: calc(100% - 260px);
            max-width: 1100px;
            padding: 45px;
        }

        .header {
            margin-bottom: 40px;
        }

        .header h1 {
            margin: 0 0 10px;
            font-size: 34px;
        }

        .header p {
            color: #6b7280;
            line-height: 1.7;
        }

        .base-url {
            background: #fff;
            border: 1px solid #e1e4e8;
            border-radius: 8px;
            padding: 15px 18px;
            margin-top: 20px;
            font-family: Consolas, monospace;
            font-size: 14px;
        }

        section {
            margin-bottom: 55px;
        }

        section h2 {
            font-size: 23px;
            margin-bottom: 20px;
        }

        .endpoint {
            background: #fff;
            border: 1px solid #e1e4e8;
            border-radius: 9px;
            margin-bottom: 18px;
            overflow: hidden;
        }

        .endpoint-header {
            padding: 15px 18px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .method {
            font-size: 12px;
            font-weight: 700;
            padding: 5px 8px;
            border-radius: 5px;
        }

        .get {
            background: #e8f5e9;
            color: #247a35;
        }

        .post {
            background: #e8f0fe;
            color: #2457a6;
        }

        .put {
            background: #fff4df;
            color: #9a6500;
        }

        .delete {
            background: #fde8e8;
            color: #b42323;
        }

        .path {
            font-family: Consolas, monospace;
            font-size: 14px;
        }

        .endpoint-body {
            padding: 18px;
        }

        .endpoint-body p {
            color: #60656d;
            line-height: 1.6;
        }

        .auth {
            display: inline-block;
            background: #f1f3f5;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 12px;
            font-family: Consolas, monospace;
        }

        pre {
            background: #17191c;
            color: #e5e7eb;
            padding: 18px;
            border-radius: 7px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
        }

        code {
            font-family: Consolas, Monaco, monospace;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border: 1px solid #e1e4e8;
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            text-align: left;
            padding: 13px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
        }

        td code {
            background: #f1f3f5;
            padding: 3px 6px;
            border-radius: 4px;
        }

        .note {
            border-left: 4px solid #343a40;
            background: #fff;
            padding: 15px 18px;
            margin: 20px 0;
            line-height: 1.6;
        }

        .status {
            color: #237a35;
            font-weight: 600;
        }

        @media (max-width: 800px) {

            .sidebar {
                position: static;
                width: 100%;
            }

            .layout {
                display: block;
            }

            .content {
                margin-left: 0;
                width: 100%;
                padding: 25px 18px;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <aside class="sidebar">

        <div class="brand">
            Bank Mini API
        </div>

        <div class="version">
            Customer API · v1
        </div>

        <a href="#overview">Overview</a>
        <a href="#authentication">Authentication</a>
        <a href="#dashboard">Dashboard</a>
        <a href="#profile">Profile</a>
        <a href="#balance">Balance</a>
        <a href="#barcode">Barcode</a>
        <a href="#transactions">Transactions</a>
        <a href="#errors">Response & Error</a>

    </aside>


    <main class="content">

        <header class="header" id="overview">

            <h1>Bank Mini API</h1>

            <p>
                Dokumentasi API untuk aplikasi Customer Bank Mini.
                API digunakan oleh aplikasi mobile atau frontend
                untuk mengakses data akun, dashboard, profil,
                saldo, QR code rekening, dan riwayat transaksi nasabah.
            </p>

            <div class="base-url">
                Base URL:
                <strong>http://localhost:8000/api</strong>
            </div>

        </header>


        {{-- AUTHENTICATION --}}

        <section id="authentication">

            <h2>Authentication</h2>

            <p>
                API menggunakan Laravel Sanctum.
                Setelah login berhasil, server memberikan
                token yang digunakan untuk request berikutnya.
            </p>

            <div class="endpoint">

                <div class="endpoint-header">

                    <span class="method post">
                        POST
                    </span>

                    <span class="path">
                        /customer/login
                    </span>

                </div>

                <div class="endpoint-body">

                    <p>
                        Login customer menggunakan
                        <strong>username</strong> dan password.
                    </p>

                    <table>

                        <tr>
                            <th>Field</th>
                            <th>Type</th>
                            <th>Required</th>
                        </tr>

                        <tr>
                            <td><code>username</code></td>
                            <td>string</td>
                            <td>Yes</td>
                        </tr>

                        <tr>
                            <td><code>password</code></td>
                            <td>string</td>
                            <td>Yes</td>
                        </tr>

                    </table>

                    <pre><code>{
    "username": "customer01",
    "password": "123456"
}</code></pre>

                    <pre><code>{
    "success": true,
    "message": "Login berhasil.",
    "token": "1|abcxyz...",
    "token_type": "Bearer",
    "user": {
        "id": 1,
        "username": "customer01",
        "name": "Budi Santoso",
        "role": "customer"
    },
    "customer": {
        "id": 1,
        "name": "Budi Santoso",
        "account_number": "10000001",
        "balance": 150000
    }
}</code></pre>

                </div>

            </div>


            <div class="endpoint">

                <div class="endpoint-header">

                    <span class="method post">
                        POST
                    </span>

                    <span class="path">
                        /customer/logout
                    </span>

                </div>

                <div class="endpoint-body">

                    <span class="auth">
                        Bearer Token Required
                    </span>

                </div>

            </div>

        </section>


        {{-- DASHBOARD --}}

        <section id="dashboard">

            <h2>Dashboard</h2>

            <div class="endpoint">

                <div class="endpoint-header">

                    <span class="method get">
                        GET
                    </span>

                    <span class="path">
                        /customer/dashboard
                    </span>

                </div>

                <div class="endpoint-body">

                    <span class="auth">
                        Bearer Token Required
                    </span>

                    <p>
                        Mengambil ringkasan data dashboard customer
                        yang sedang login.
                    </p>

                    <pre><code>{
    "message": "Dashboard customer berhasil diambil.",
    "data": {
        "name": "Budi Santoso",
        "account_number": "10000001",
        "balance": 150000
    }
}</code></pre>

                </div>

            </div>

        </section>


        {{-- PROFILE --}}

        <section id="profile">

            <h2>Profile</h2>

            <div class="endpoint">

                <div class="endpoint-header">

                    <span class="method get">
                        GET
                    </span>

                    <span class="path">
                        /customer/profile
                    </span>

                </div>

                <div class="endpoint-body">

                    <span class="auth">
                        Bearer Token Required
                    </span>

                    <p>
                        Mengambil informasi customer yang
                        sedang login.
                    </p>

                    <pre><code>{
    "message": "Profile berhasil diambil.",
    "data": {
        "id": 1,
        "name": "Nama Nasabah",
        "account_number": "10000001",
        "nis": "1234567890",
        "class": "XII",
        "location": "Jakarta",
        "telephone_number": "081234567890",
        "email": "nasabah@example.com",
        "balance": 100000
    }
}</code></pre>

                </div>

            </div>


            <div class="endpoint">

                <div class="endpoint-header">

                    <span class="method put">
                        PUT
                    </span>

                    <span class="path">
                        /customer/profile
                    </span>

                </div>

                <div class="endpoint-body">

                    <span class="auth">
                        Bearer Token Required
                    </span>

                    <p>
                        Mengubah data profil customer.
                    </p>

                    <pre><code>{
    "name": "Nama Customer",
    "address": "Jl. Merdeka No. 12",
    "phone": "081234567890"
}</code></pre>

                    <p>
                        Field yang dapat diubah: <code>name</code>, <code>address</code>, <code>phone</code>.
                    </p>

                </div>

            </div>

        </section>


        {{-- BALANCE --}}

        <section id="balance">

            <h2>Balance</h2>

            <div class="endpoint">

                <div class="endpoint-header">

                    <span class="method get">
                        GET
                    </span>

                    <span class="path">
                        /customer/balance
                    </span>

                </div>

                <div class="endpoint-body">

                    <span class="auth">
                        Bearer Token Required
                    </span>

                    <p>
                        Mengambil saldo customer yang sedang login.
                    </p>

                    <pre><code>{
    "message": "Balance retrieved successfully",
    "data": {
        "balance": 150000
    }
}</code></pre>

                </div>

            </div>

        </section>


        {{-- BARCODE --}}

        <section id="barcode">

            <h2>Barcode / QR Code Rekening</h2>

            <div class="endpoint">

                <div class="endpoint-header">

                    <span class="method get">
                        GET
                    </span>

                    <span class="path">
                        /customer/barcode
                    </span>

                </div>

                <div class="endpoint-body">

                    <span class="auth">
                        Bearer Token Required
                    </span>

                    <p>
                        Mengambil data QR code rekening customer saat ini.
                        Endpoint mengembalikan URL QR code dan data SVG Base64
                        agar dapat langsung ditampilkan oleh aplikasi.
                    </p>

                    <pre><code>{
    "message": "Barcode customer berhasil diambil.",
    "data": {
        "id": 1,
        "account_number": "10000001",
        "qrcode": "10000001.svg",
        "qrcode_url": "http://localhost:8000/qrcode/10000001.svg",
        "qrcode_data_uri": "data:image/svg+xml;base64,..."
    }
}</code></pre>

                </div>

            </div>

        </section>


        {{-- TRANSACTIONS --}}

        <section id="transactions">

            <h2>Transactions</h2>

            <div class="endpoint">

                <div class="endpoint-header">

                    <span class="method get">
                        GET
                    </span>

                    <span class="path">
                        /customer/transactions
                    </span>

                </div>

                <div class="endpoint-body">

                    <span class="auth">
                        Bearer Token Required
                    </span>

                    <p>
                        Mengambil seluruh riwayat transaksi
                        customer yang sedang login.
                    </p>

                    <pre><code>{
    "message": "Data transaksi berhasil diambil.",
    "data": [
        {
            "id": 1,
            "transaction_type": "setoran",
            "amount": 50000,
            "beginning_balance": 100000,
            "ending_balance": 150000,
            "date": "2026-08-26 10:00:00"
        }
    ]
}</code></pre>

                </div>

            </div>


            <div class="endpoint">

                <div class="endpoint-header">

                    <span class="method get">
                        GET
                    </span>

                    <span class="path">
                        /customer/transactions/{id}
                    </span>

                </div>

                <div class="endpoint-body">

                    <span class="auth">
                        Bearer Token Required
                    </span>

                    <p>
                        Mengambil detail satu transaksi
                        milik customer yang sedang login.
                    </p>

                </div>

            </div>

        </section>


        {{-- ERRORS --}}

        <section id="errors">

            <h2>Response & Error</h2>

            <table>

                <tr>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>

                <tr>
                    <td><code>200</code></td>
                    <td>Request berhasil</td>
                </tr>

                <tr>
                    <td><code>201</code></td>
                    <td>Data berhasil dibuat</td>
                </tr>

                <tr>
                    <td><code>401</code></td>
                    <td>Token tidak valid atau belum login</td>
                </tr>

                <tr>
                    <td><code>403</code></td>
                    <td>Akses ditolak</td>
                </tr>

                <tr>
                    <td><code>404</code></td>
                    <td>Data tidak ditemukan</td>
                </tr>

                <tr>
                    <td><code>422</code></td>
                    <td>Validasi gagal</td>
                </tr>

                <tr>
                    <td><code>500</code></td>
                    <td>Kesalahan server</td>
                </tr>

            </table>

            <div class="note">

                <strong>Authorization Header</strong>

                <pre><code>Authorization: Bearer YOUR_TOKEN</code></pre>

            </div>

        </section>


        <footer style="color:#777; font-size:13px;">
            Bank Mini API · Laravel · Customer Application
        </footer>

    </main>

</div>

</body>
</html>