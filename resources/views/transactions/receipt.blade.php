<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Bukti Transaksi #{{ $transaction->id }}</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            margin: 0;
            padding: 30px;
        }

        .receipt {
            width: 700px;
            max-width: 100%;
            margin: auto;
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
        }

        .header p {
            margin: 5px 0;
        }

        .title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 8px 5px;
            vertical-align: top;
        }

        td:first-child {
            width: 180px;
            font-weight: bold;
        }

        .line {
            border-top: 1px solid #000;
            margin: 20px 0;
        }

        .amount {
            font-size: 22px;
            font-weight: bold;
        }

        .success {
            text-align: center;
            font-weight: bold;
            margin-top: 25px;
            font-size: 18px;
        }

        .buttons {
            text-align: center;
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            margin: 5px;
            text-decoration: none;
            border-radius: 5px;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-print {
            background: #198754;
        }

        .btn-back {
            background: #6c757d;
        }

        .btn-continue {
            background: #0d6efd;
        }

        @media print {

            body {
                background: white;
                padding: 0;
            }

            .receipt {
                width: 100%;
                border: none;
                padding: 10px;
            }

            .buttons {
                display: none;
            }

        }

    </style>

</head>


<body>

<div class="receipt">

    {{-- HEADER --}}

    <div class="header">

        <h2>BANK MINI SEKOLAH</h2>

        <p>Sistem Informasi E-Teller Bank Mini</p>

    </div>


    <div class="title">

        BUKTI TRANSAKSI

    </div>


    {{-- DATA TRANSAKSI --}}

    <table>

        <tr>

            <td>No. Transaksi</td>

            <td>
                #{{ $transaction->id }}
            </td>

        </tr>


        <tr>

            <td>Tanggal</td>

            <td>
                {{ $transaction->date->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }} WIB
            </td>

        </tr>


        <tr>

            <td>Teller</td>

            <td>
                {{ $transaction->teller->officer_name ?? '-' }}
            </td>

        </tr>

    </table>


    <div class="line"></div>


    {{-- DATA CUSTOMER --}}

    <table>

        <tr>

            <td>Nama Nasabah</td>

            <td>
                {{ $transaction->customer->name ?? '-' }}
            </td>

        </tr>


        <tr>

            <td>No. Rekening</td>

            <td>
                {{ $transaction->customer->account_number ?? '-' }}
            </td>

        </tr>


        <tr>

            <td>NIS</td>

            <td>
                {{ $transaction->customer->nis ?? '-' }}
            </td>

        </tr>


        <tr>

            <td>Kelas</td>

            <td>
                {{ $transaction->customer->class ?? '-' }}
            </td>

        </tr>

    </table>


    <div class="line"></div>


    {{-- DETAIL TRANSAKSI --}}

    <table>

        <tr>

            <td>Jenis Transaksi</td>

            <td>

                @if($transaction->transaction_type == 'setoran')

                    <strong>SETORAN</strong>

                @else

                    <strong>PENARIKAN</strong>

                @endif

            </td>

        </tr>


        <tr>

            <td>Saldo Awal</td>

            <td>
                Rp {{ number_format($transaction->beginning_balance, 0, ',', '.') }}
            </td>

        </tr>


        <tr>

            <td>Jumlah Transaksi</td>

            <td class="amount">
                Rp {{ number_format($transaction->amount, 0, ',', '.') }}
            </td>

        </tr>


        <tr>

            <td>Saldo Akhir</td>

            <td class="amount">
                Rp {{ number_format($transaction->ending_balance, 0, ',', '.') }}
            </td>

        </tr>

    </table>


    <div class="line"></div>


    <div class="success">

        TRANSAKSI BERHASIL

    </div>


    {{-- BUTTON --}}

    <div class="buttons">

        <button
            onclick="window.print()"
            class="btn btn-print"
        >
            🖨 Cetak Bukti
        </button>


        <a
            href="{{ route('teller.dashboard') }}"
            class="btn btn-back"
        >
            Kembali ke Beranda
        </a>

        <a
            href="{{ route('transactions.create', ['customer_id' => $transaction->customer_id]) }}"
            class="btn btn-continue"
        >
            Lanjut Transaksi
        </a>

    </div>

</div>

</body>

</html>