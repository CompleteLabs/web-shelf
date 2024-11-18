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
        // Hilangkan header baris jika ada
        $filteredCollection = $collection->filter(function ($row) {
            return $row[0] !== 'ID INPUT';
        });

        // Sorting berdasarkan ID INPUT dengan mengekstrak angka
        $sortedCollection = $filteredCollection->sortBy(function ($row) {
            // Ambil bagian angka dari ID INPUT
            preg_match('/\d+$/', $row[0], $matches);
            return $matches[0] ?? 0; // Default ke 0 jika tidak ditemukan
        });

        foreach ($sortedCollection as $row) {
            // Parsing departure_time
            $departureTime = null;
            if (!empty($row[4])) {
                try {
                    if (is_numeric($row[4])) {
                        $date = Carbon::instance(Date::excelToDateTimeObject($row[4]));
                        $departureTime = $date->format('Y-m-d H:i:s');
                    } else {
                        $cleanedDepartureTime = trim($row[4]);
                        $cleanedDepartureTime = str_replace(['/', '.'], ['-', ':'], $cleanedDepartureTime);
                        $departureTime = Carbon::createFromFormat('Y-m-d H:i', $cleanedDepartureTime)->format('Y-m-d H:i:s');
                    }
                } catch (\Exception $e) {
                    $departureTime = null;
                }
            }

            // Parsing return_time
            $returnTime = null;
            if (!empty($row[5])) {
                try {
                    if (is_numeric($row[5])) {
                        $date = Carbon::instance(Date::excelToDateTimeObject($row[5]));
                        $returnTime = $date->format('Y-m-d H:i:s');
                    } else {
                        $cleanedReturnTime = trim($row[5]);
                        $cleanedReturnTime = str_replace(['/', '.'], ['-', ':'], $cleanedReturnTime);
                        $returnTime = Carbon::createFromFormat('Y-m-d H:i', $cleanedReturnTime)->format('Y-m-d H:i:s');
                    }
                } catch (\Exception $e) {
                    $returnTime = null;
                }
            }

            // Insert data into vehicle_checksheet table
            VehicleChecksheet::create([
                'reference_number' => $row[0] ?? null, // ID INPUT
                'start_km' => isset($row[1]) && is_numeric($row[1]) ? $row[1] : null,
                'end_km' => isset($row[2]) && is_numeric($row[2]) ? $row[2] : null,
                'pic' => $row[3] ?? null,
                'departure_time' => $departureTime,
                'return_time' => $returnTime,
                'license_plate' => $row[6] ?? null,
                'departure_photo' => $row[7] ?? null,
                'return_photo' => $row[8] ?? null,
                'departure_damage_report' => $row[9] ?? null,
                'return_damage_report' => $row[10] ?? null,
                'location' => $row[11] ?? null,
                'remarks' => $row[12] ?? null,
                'rental_duration' => isset($row[13]) && is_numeric($row[13]) ? $row[13] : null,
                'distance_traveled' => isset($row[14]) && is_numeric($row[14]) ? $row[14] : 0,
            ]);
        }
    }
}
