<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CustomAssetAttribute;
use App\Models\AssetAttribute;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CustomAssetNotification;
use Carbon\Carbon;

class SendScheduledNotifications extends Command
{
    protected $signature = 'notifications:send-scheduled';
    protected $description = 'Mengirim notifikasi terjadwal berdasarkan konfigurasi CustomAssetAttribute';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Ambil semua CustomAssetAttributes yang dapat diberi notifikasi
        $attributes = CustomAssetAttribute::where('is_notifiable', true)->get();

        foreach ($attributes as $attribute) {
            if ($attribute->notification_type === 'relative_date' && $attribute->notification_offset) {
                $this->handleRelativeDateNotification($attribute);
            }

            if ($attribute->notification_type === 'fixed_date' && $attribute->fixed_notification_date) {
                $this->handleFixedDateNotification($attribute);
            }
        }

        $this->info('Proses pengiriman notifikasi terjadwal selesai.');
    }


    protected function handleRelativeDateNotification($attribute)
    {
        $assetAttributes = $attribute->assetAttributes()
            ->whereNotNull('attribute_value')
            ->with(['asset.assetLocation'])
            ->get();

        foreach ($assetAttributes as $assetAttribute) {
            $endDate = Carbon::parse($assetAttribute->attribute_value);
            $startDate = $endDate->copy()->subDays($attribute->notification_offset);

            if (Carbon::now()->between($startDate, $endDate)) {
                $assetName = $assetAttribute->asset->name ?? 'N/A';
                $locationName = $assetAttribute->asset->assetLocation->name ?? 'N/A';

                $message = "🔔 *Notifikasi Pengingat Aset* 🔔\n\n";
                $message .= "Halo, berikut pengingat untuk aset Anda:\n\n";
                $message .= "📦 *Nama Aset*: {$assetName}\n";
                $message .= "🔖 *Atribut*: {$attribute->name}\n";
                $message .= "📅 *Nilai Atribut*: {$assetAttribute->attribute_value}\n";
                $message .= "📍 *Lokasi*: {$locationName}\n\n";
                $message .= "Pesan ini untuk mengingatkan bahwa atribut *{$attribute->name}* pada aset Anda membutuhkan perhatian khusus. Harap periksa aset tersebut untuk menghindari masalah di masa depan.\n\n";
                $message .= "— Bot";
                $this->sendNotification($message, $assetAttribute->asset);
            }
        }

        unset($assetAttributes);
    }


    protected function handleFixedDateNotification($attribute)
    {
        $fixedDate = Carbon::parse($attribute->fixed_notification_date);

        if (Carbon::now()->isSameDay($fixedDate)) {
            // Mengirim notifikasi
            $message = "🔔 *Notifikasi Atribut Tetap* 🔔\n\n";
            $message .= "Atribut {$attribute->name} memiliki notifikasi pada tanggal tetap.\n\n—";
            $this->sendNotification($message, null);
        }
    }

    protected function sendNotification($message, $asset)
    {
        // Contoh pengiriman pesan via WhatsApp menggunakan Guzzle Client
        $client = new Client();
        $apiEndpoint = env('WHATSAPP_API_ENDPOINT');
        $phoneNumber = env('DEFAULT_NOTIFICATION_PHONE');

        try {
            $response = $client->post($apiEndpoint, [
                'query' => [
                    'apikey' => env('WHATSAPP_API_KEY'),
                    'sender' => env('WHATSAPP_SENDER_NUMBER'),
                    'receiver' => $phoneNumber,
                    'message' => $message
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                \Log::error('Gagal mengirim pesan WhatsApp: ' . $response->getBody());
            }
        } catch (\Exception $e) {
            \Log::error('Gagal mengirim pesan WhatsApp: ' . $e->getMessage());
        }
    }
}
