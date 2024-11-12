<?php

namespace App\Imports;

use App\Models\VehicleChecksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class VehicleChecksheetImport implements ToCollection
{
    /**
     * Handle the collection of data from the Excel file.
     *
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            // Skip header row (adjust based on your actual header)
            if ($row[0] == 'ID INPUT') {
                continue;
            }

            // Parsing untuk departure_time
            $departureTime = null;
            if (!empty($row[4])) {
                try {
                    // Cek apakah nilai adalah angka, mengindikasikan format tanggal serial Excel
                    if (is_numeric($row[4])) {
                        // Konversi nilai Excel date serial ke format tanggal
                        $date = Carbon::instance(Date::excelToDateTimeObject($row[4]));
                        $departureTime = $date->format('Y-m-d H:i:s'); // Ubah format sesuai dengan kebutuhan database
                    } else {
                        // Lakukan parsing normal jika bukan angka atau tanggal serial
                        $cleanedDepartureTime = trim($row[4]);
                        $cleanedDepartureTime = str_replace(['/', '.'], ['-', ':'], $cleanedDepartureTime);
                        $departureTime = Carbon::createFromFormat('Y-m-d H:i', $cleanedDepartureTime)->format('Y-m-d H:i:s');
                    }
                } catch (\Exception $e) {
                    $departureTime = null; // Set to null jika parsing gagal
                }
            } else {
                $departureTime = null; // Tangani nilai kosong dengan mengatur ke null
            }

            // Parsing untuk return_time
            $returnTime = null;
            if (!empty($row[5])) {
                try {
                    // Cek apakah nilai adalah angka, mengindikasikan format tanggal serial Excel
                    if (is_numeric($row[5])) {
                        // Konversi nilai Excel date serial ke format tanggal
                        $date = Carbon::instance(Date::excelToDateTimeObject($row[5]));
                        $returnTime = $date->format('Y-m-d H:i:s'); // Ubah format sesuai dengan kebutuhan database
                    } else {
                        // Lakukan parsing normal jika bukan angka atau tanggal serial
                        $cleanedReturnTime = trim($row[5]);
                        $cleanedReturnTime = str_replace(['/', '.'], ['-', ':'], $cleanedReturnTime);
                        $returnTime = Carbon::createFromFormat('Y-m-d H:i', $cleanedReturnTime)->format('Y-m-d H:i:s');
                    }
                } catch (\Exception $e) {
                    $returnTime = null; // Set to null jika parsing gagal
                }
            } else {
                $returnTime = null; // Tangani nilai kosong dengan mengatur ke null
            }

            // Insert data into vehicle_checksheet table
            VehicleChecksheet::create([
                'reference_number' => $row[0] ?? null, // ID INPUT
                'start_km' => isset($row[1]) && is_numeric($row[1]) ? $row[1] : null, // KM. Awal
                'end_km' => isset($row[2]) && is_numeric($row[2]) ? $row[2] : null, // KM. Akhir
                'pic' => $row[3] ?? null, // PIC
                'departure_time' => $departureTime, // Jam Keluar
                'return_time' => $returnTime, // Jam Kembali
                'license_plate' => $row[6] ?? null, // Plat Nomer
                'departure_photo' => $row[7] ?? null, // Foto Keluar
                'return_photo' => $row[8] ?? null, // Foto Kembali
                'departure_damage_report' => $row[9] ?? null, // Checksheet Damage Keluar
                'return_damage_report' => $row[10] ?? null, // Checksheet Damage Kembali
                'location' => $row[11] ?? null, // Lokasi
                'remarks' => $row[12] ?? null, // Keterangan
                'rental_duration' => isset($row[13]) && is_numeric($row[13]) ? $row[13] : null, // Durasi Pinjam
                'distance_traveled' => isset($row[14]) && is_numeric($row[14]) ? $row[14] : 0, // Jarak Tempuh
            ]);
        }
    }
}
