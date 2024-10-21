<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Pengerjaan</title>
    <style>
        @page {
            margin: 1cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin: 0;
            padding: 0;
        }

        .content {
            margin: 32px;
        }

        h1, h2 {
            text-align: center;
            font-size: 18px;
            margin: 0;
            padding: 0;
            text-transform: uppercase;
        }

        .details-table {
            margin-bottom: 20px;
        }

        .signature-table {
            margin-top: 40px;
        }

        .details-table, .signature-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 16px;
        }

        .details-table td, .signature-table td {
            padding: 10px;
            vertical-align: top;
        }

        .details-table td {
            border: none;
            margin: 0;
            padding: 1px;
        }

        .signature-table td {
            text-align: center;
            width: 50%;
            height: 80px;
            border: none;
        }

        .details p, .signature-space p {
            margin: 0;
            font-size: 16px;
        }

        .signature-space {
            height: 80px;
        }

        .justify {
            text-align: justify;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Berita Acara Pengerjaan {{ $task->name }}</h1>
        <h2>Nomer Dokumen: {{ $task->id }}</h2>
    </div>

    <div class="content">
        <p>Pada hari <strong>{{ $task->updated_at->translatedFormat('l, d F Y H:i') }}</strong>, telah dilakukan pengerjaan berupa:</p>

        <table class="details-table">
            <tr>
                <td style="width: 30%;"><strong>Detail Pengerjaan:</strong></td>
                <td style="width: 70%;">{{ $task->name }}</td>
            </tr>
            <tr>
                <td><strong>Deskripsi:</strong></td>
                <td>{{ $task->description }}</td>
            </tr>
            <tr>
                <td><strong>Tempat Pelaksanaan:</strong></td>
                <td>{{ $task->location }}</td>
            </tr>
            <tr>
                <td><strong>Harga Pengerjaan:</strong></td>
                <td>Rp {{ number_format($task->cost, 2) }}</td>
            </tr>
        </table>

        <p>Dengan ini, disampaikan bahwa pengerjaan telah <strong>selesai</strong>.</p>

        <table class="signature-table">
            <tr>
                <td>Mengetahui,<br><br><br><br><br><strong>HR Manager</strong></td>
                <td>Pelaksana<br><br><br><br><br><strong>GA</strong></td>
            </tr>
        </table>
    </div>
</body>

</html>
