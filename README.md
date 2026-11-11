<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Sistem Informasi E-Teller Bank Mini Sekolah

**BANK MINI SMKN 1 BANTUL**

Sistem ini mendukung layanan Bank Mini sekolah sekaligus pembelajaran praktik operasional perbankan. Website pengurus dipakai untuk mengelola data nasabah dan petugas, transaksi teller, kas, laporan, otorisasi, dan pemeriksaan. Repository ini berisi backend dan website berbasis Laravel, serta satu berkas APK untuk nasabah.

Website resmi: https://etellerpoy.my.id/

## Gambaran Umum

Pengunjung yang belum login melihat landing page, sedangkan pengguna yang sudah login diarahkan ke dashboard sesuai role: admin, teller, atau supervisor. Landing page juga menyediakan tautan untuk mengunduh APK nasabah. Aplikasi Android berkomunikasi dengan REST API Laravel yang dilindungi Laravel Sanctum.

> Source Flutter dan konfigurasi API aplikasi Android tidak ada di repository ini.

## Teknologi

| Teknologi | Fungsi |
|---|---|
| PHP 8.3 dan Laravel 13 | Backend, website pengurus, dan API |
| Laravel Sanctum | Autentikasi API |
| MySQL | Database (diatur lewat `.env`) |
| Vite dan Tailwind CSS | Build dan styling frontend |
| DomPDF | Pembuatan PDF |
| Simple QR Code | Pembuatan QR code |

## Fitur Berdasarkan Role

**Administrator**
- Mengelola saldo awal brankas, konfigurasi, dan backup
- Mengelola data nasabah dan petugas, termasuk impor dan ekspor
- Mengatur jadwal petugas dan memproses permintaan reset PIN
- Verifikasi transaksi harian
- Pada laporan dan review bersama, akses hanya bisa melihat

**Teller**
- Pencarian nasabah dan transaksi beserta status otorisasi
- Pengambilan kas dari brankas
- Cetak struk dan laporan kas harian
- Pengajuan koreksi

**Supervisor**
- Verifikasi laporan dan audit
- Persetujuan atau penolakan laporan dan koreksi
- Memproses permintaan PIN
- Laporan operasional dan akuntansi

**Nasabah (aplikasi Android)**
- Aktivasi akun dan masuk dengan NIS dan PIN
- Melihat ringkasan rekening dan mutasi transaksi
- Membuka QR rekening dan mengajukan perubahan PIN
- Menerima notifikasi

## Panduan Website

Buka website resmi, pilih **Login Petugas**, lalu masuk dengan akun yang sah. Setelah login, pengguna masuk ke dashboard sesuai role. Jangan membagikan password kepada siapa pun.

**Administrator:** siapkan konfigurasi dan saldo awal brankas, kelola akun petugas dan data nasabah, atur jadwal, serta proses permintaan reset PIN.

**Teller:** cari nasabah, isi transaksi, dan tunggu persetujuan nasabah bila diperlukan. Transaksi dianggap selesai setelah statusnya berhasil. Setelah itu cetak struk, susun dan kirim laporan harian, dan ajukan koreksi jika ada kesalahan (transaksi asli tidak dihapus).

**Supervisor:** periksa laporan teller sebelum menyetujui atau menolaknya, tinjau audit dan verifikasi harian, serta proses koreksi sesuai bukti.

## Panduan Aplikasi Android

1. **Persiapan.** Unduh APK lewat tautan **Unduh Berkas .APK Resmi** di landing page, lalu pasang di perangkat Android. Pastikan aplikasi terhubung ke server backend. Jika memakai ponsel fisik saat pengembangan, arahkan alamat server ke IP komputer yang menjalankan backend.
2. **Masuk atau aktivasi akun.** Jika sudah punya akun, pilih menu masuk lalu masukkan NIS dan PIN. Jika belum aktif, minta kode, verifikasi kode, lalu buat kata sandi dan PIN.
3. **Menu utama.**
   - **Beranda:** ringkasan rekening dan transaksi.
   - **Mutasi:** melihat, mencari, dan memfilter transaksi, serta membuka detail transaksi.
   - **Saya:** informasi rekening, pengaturan akun, QR saya, dan menu PIN.
   - **Notifikasi:** ketuk ikon lonceng untuk melihat notifikasi. Tersedia tindakan **Tandai Semua Dibaca**.
4. **Mengelola PIN dan akun.** Gunakan menu PIN untuk mengajukan perubahan PIN. Menu Akun menyediakan pengaturan akun dan pilihan untuk keluar. Jangan membagikan PIN atau kode aktivasi kepada siapa pun.

> **Catatan:** Status pengajuan PIN belum terhubung ke backend. Fitur cetak mutasi, unggah foto profil, dan pemulihan kata sandi melalui OTP belum tersedia.

## Aturan Bisnis dan Keamanan

- Saldo minimum yang mengendap saat penarikan adalah Rp10.000 secara default dan bisa diubah lewat konfigurasi.
- Penarikan besar mulai Rp1.000.000 (default) memerlukan pemeriksaan konfirmasi.
- Otorisasi nasabah menunggu persetujuan selama 5 menit.
- Koreksi diajukan teller dan disetujui atau ditolak supervisor.
- PIN disimpan dalam bentuk hash. Jangan pernah meminta atau mencatat PIN nasabah secara terbuka.
- Akses web dibatasi oleh login dan role, sedangkan API dilindungi `auth:sanctum`.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
