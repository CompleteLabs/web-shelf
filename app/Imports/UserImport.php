<?php

namespace App\Imports;

use App\Models\BusinessEntity;
use App\Models\JobTitle;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;

class UserImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction(); // Memulai transaksi untuk memastikan atomisitas

        try {
            foreach ($rows as $row) {
                // Lewati baris header dan baris di mana nama kosong
                if ($row[0] !== 'Nama' && !empty($row[0])) {
                    // Validasi bahwa semua kolom yang diperlukan terisi
                    if (empty($row[1]) || empty($row[2])) {
                        Log::error('Data untuk Badan Usaha atau Jabatan hilang', [
                            'baris' => $row
                        ]);
                        continue; // Lewati baris ini jika data tidak lengkap
                    }

                    // Cari atau buat entitas bisnis
                    $businessEntity = BusinessEntity::firstOrCreate(
                        ['name' => $row[1]],
                        ['created_at' => now(), 'updated_at' => now()]
                    );

                    // Cari atau buat jabatan
                    $jobTitle = JobTitle::firstOrCreate(
                        ['title' => $row[2]],
                        ['created_at' => now(), 'updated_at' => now()]
                    );

                    // Buat user baru jika semua sudah sesuai
                    User::create([
                        'name' => $row[0],
                        'business_entity_id' => $businessEntity->id,
                        'job_title_id' => $jobTitle->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit(); // Komit transaksi jika semua berjalan baik
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi kesalahan
            DB::rollBack();

            // Log kesalahan untuk investigasi lebih lanjut
            Log::error('Kesalahan saat mengimpor data pengguna', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Lempar pengecualian jika ingin menghentikan proses
            throw ValidationException::withMessages(['import' => 'Terjadi kesalahan saat impor pengguna. Silakan periksa log untuk detail lebih lanjut.']);
        }
    }
}
