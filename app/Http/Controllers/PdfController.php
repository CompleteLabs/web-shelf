<?php

namespace App\Http\Controllers;

use App\Models\AssetTransfer;
use App\Models\Task;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function downloadAssetTransfer($id)
    {
        $assetTransfer = AssetTransfer::with('fromUser.jobTitle', 'toUser.jobTitle', 'businessEntity', 'details.asset.category', 'details.asset.brand', 'details.asset.attributes')->findOrFail($id);

        // dd($assetTransfer);

        // Pemetaan status ke singkatan
        $statusMap = [
            'BERITA ACARA SERAH TERIMA' => 'BA',
            'BERITA ACARA PENGALIHAN BARANG' => 'BAPAB',
            'BERITA ACARA PENGEMBALIAN BARANG' => 'BAPEB',
        ];

        $status = $statusMap[$assetTransfer->status] ?? 'UNKNOWN';

        // Menggunakan nilai dari kolom letterhead, atau default image jika tidak ada
        $headerImage = $assetTransfer->businessEntity->letterhead
            ? asset('storage/' . $assetTransfer->businessEntity->letterhead)
            : asset('images/cvcs_kop.png');



        $letterNumber = $assetTransfer->letter_number;
        $toUserName = strtolower(str_replace(' ', '_', $assetTransfer->toUser->name));
        $toUserJobTitle = $assetTransfer->toUser->jobTitle ? strtolower(str_replace(' ', '_', $assetTransfer->toUser->jobTitle->title)) : 'no_title';

        $fileName = "{$status}_{$letterNumber}_{$toUserName}_{$toUserJobTitle}.pdf";

        $pdf = Pdf::loadView('pdf.asset-transfer', compact('assetTransfer', 'headerImage'));

        return $pdf->download($fileName);
    }

    public function downloadTaskCompletion($id)
    {
        $task = Task::with('businessEntity')->findOrFail($id);

        // Menggunakan nilai dari kolom letterhead di entitas bisnis terkait, atau default image jika tidak ada
        $headerImage = $task->businessEntity->letterhead
            ? asset('storage/' . $task->businessEntity->letterhead)
            : asset('images/cvcs_kop.png');

        // Ganti spasi dengan underscore dan ubah jadi huruf kecil semua untuk penamaan file
        $fileName = strtolower(str_replace(' ', '_', $task->name));

        // Siapkan lampiran
        $attachments = collect(json_decode($task->attachment))->map(function ($image) {
            $baseUrl = asset('storage'); // Path dasar menuju file di storage Laravel
            return "<img src='{$baseUrl}/{$image}' alt='Lampiran'>";
        })->implode('');

        // Buat PDF dengan kop surat (headerImage), task, dan lampiran
        $pdf = PDF::loadView('pdf.task-completion', compact('task', 'headerImage', 'attachments'));

        // Download file PDF
        // return $pdf->download('berita_acara_pengerjaan_' . $fileName . '.pdf');

        // Stream PDF untuk preview di browser
        return $pdf->stream('berita_acara_pengerjaan_' . $fileName . '.pdf');

        // return view('pdf.task-completion', compact('task', 'headerImage', 'attachments'));
    }
}
