<?php

namespace App\Http\Controllers;

use App\Models\AssetTransfer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function downloadAssetTransfer($id)
    {
        $assetTransfer = AssetTransfer::with('fromUser.jobTitle', 'toUser.jobTitle', 'businessEntity', 'details.asset.category', 'details.asset.brand')->findOrFail($id);

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
}
