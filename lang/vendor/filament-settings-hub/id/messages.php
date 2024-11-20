<?php

return [
    'title' => 'Pengaturan',
    'group' => 'Pengaturan',
    'back' => 'Kembali',
    'settings' => [
        'site' => [
            'title' => 'Pengaturan Situs',
            'description' => 'Kelola pengaturan situs Anda',
            'form' => [
                'site_name' => 'Nama Situs',
                'site_description' => 'Deskripsi Situs',
                'site_logo' => 'Logo Situs',
                'site_profile' => 'Gambar Profil Situs',
                'site_keywords' => 'Kata Kunci Situs',
                'site_email' => 'Email Situs',
                'site_phone' => 'Telepon Situs',
                'site_author' => 'Penulis Situs',
            ],
            'site-map' => 'Buat Peta Situs',
            'site-map-notification' => 'Peta Situs Berhasil Dibuat',
        ],
        'social' => [
            'title' => 'Menu Sosial',
            'description' => 'Kelola menu sosial Anda',
            'form' => [
                'site_social' => 'Tautan Sosial',
                'vendor' => 'Penyedia',
                'link' => 'Tautan',
            ],
        ],
        'location' => [
            'title' => 'Pengaturan Lokasi',
            'description' => 'Kelola pengaturan lokasi Anda',
            'form' => [
                'site_address' => 'Alamat Situs',
                'site_phone_code' => 'Kode Telepon Situs',
                'site_location' => 'Lokasi Situs',
                'site_currency' => 'Mata Uang Situs',
                'site_language' => 'Bahasa Situs',
            ],
        ],
    ],
];
