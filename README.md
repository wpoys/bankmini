<h1 align="center">Sistem Informasi E-Teller Bank Mini Sekolah</h1> <p align="center"><b>BANK MINI SMKN 1 BANTUL</b></p>

Sistem ini mendukung layanan Bank Mini sekolah sekaligus pembelajaran praktik operasional perbankan. Website pengurus dipakai untuk mengelola data nasabah dan petugas, transaksi teller, kas, laporan, otorisasi, dan pemeriksaan. Repository ini berisi backend dan website berbasis Laravel, serta satu berkas APK untuk nasabah.

Website resmi: https://etellerpoy.my.id/

Daftar Isi
Gambaran Umum
Teknologi
Fitur Berdasarkan Role
Panduan Website
Panduan Aplikasi Android
Aturan Bisnis dan Keamanan
Lisensi
Gambaran Umum

Pengunjung yang belum login melihat landing page, sedangkan pengguna yang sudah login diarahkan ke dashboard sesuai role: admin, teller, atau supervisor. Landing page juga menyediakan tautan untuk mengunduh APK nasabah. Aplikasi Android berkomunikasi dengan REST API Laravel yang dilindungi Laravel Sanctum.

Source Flutter dan konfigurasi API aplikasi Android tidak ada di repository ini.

Teknologi
Teknologi	Fungsi
PHP 8.3 dan Laravel 13	Backend, website pengurus, dan API
Laravel Sanctum	Autentikasi API
MySQL	Database (diatur lewat .env)
Vite dan Tailwind CSS	Build dan styling frontend
DomPDF	Pembuatan PDF
Simple QR Code	Pembuatan QR code
Fitur Berdasarkan Role

Administrator

Mengelola saldo awal brankas, konfigurasi, dan backup
Mengelola data nasabah dan petugas, termasuk impor dan ekspor
Mengatur jadwal petugas dan memproses permintaan reset PIN
Verifikasi transaksi harian
Pada laporan dan review bersama, akses hanya bisa melihat

Teller

Pencarian nasabah dan transaksi beserta status otorisasi
Pengambilan kas dari brankas
Cetak struk dan laporan kas harian
Pengajuan koreksi

Supervisor

Verifikasi laporan dan audit
Persetujuan atau penolakan laporan dan koreksi
Memproses permintaan PIN
Laporan operasional dan akuntansi

Nasabah (aplikasi Android)

Aktivasi akun dan masuk dengan NIS dan PIN
Melihat ringkasan rekening dan mutasi transaksi
Membuka QR rekening dan mengajukan perubahan PIN
Menerima notifikasi
Panduan Website

Buka website resmi, pilih Login Petugas, lalu masuk dengan akun yang sah. Setelah login, pengguna masuk ke dashboard sesuai role. Jangan membagikan password kepada siapa pun.

Administrator: siapkan konfigurasi dan saldo awal brankas, kelola akun petugas dan data nasabah, atur jadwal, serta proses permintaan reset PIN.

Teller: cari nasabah, isi transaksi, dan tunggu persetujuan nasabah bila diperlukan. Transaksi dianggap selesai setelah statusnya berhasil. Setelah itu cetak struk, susun dan kirim laporan harian, dan ajukan koreksi jika ada kesalahan (transaksi asli tidak dihapus).

Supervisor: periksa laporan teller sebelum menyetujui atau menolaknya, tinjau audit dan verifikasi harian, serta proses koreksi sesuai bukti.

Panduan Aplikasi Android
1. Persiapan

Unduh APK lewat tautan Unduh Berkas .APK Resmi di landing page, lalu pasang di perangkat Android. Pastikan aplikasi terhubung ke server backend. Jika memakai ponsel fisik saat pengembangan, arahkan alamat server ke IP komputer yang menjalankan backend.

2. Masuk atau aktivasi akun
Sudah punya akun: pilih menu masuk, lalu masukkan NIS dan PIN.
Belum aktif: minta kode, verifikasi kode, lalu buat kata sandi dan PIN.
3. Menu utama
Beranda: ringkasan rekening dan transaksi.
Mutasi: melihat, mencari, dan memfilter transaksi, serta membuka detail transaksi.
Saya: informasi rekening, pengaturan akun, QR saya, dan menu PIN.
Notifikasi: ketuk ikon lonceng untuk melihat notifikasi. Tersedia tindakan Tandai Semua Dibaca.
4. Mengelola PIN dan akun

Gunakan menu PIN untuk mengajukan perubahan PIN. Menu Akun menyediakan pengaturan akun dan pilihan untuk keluar. Jangan membagikan PIN atau kode aktivasi kepada siapa pun.

Catatan: Status pengajuan PIN belum terhubung ke backend. Fitur cetak mutasi, unggah foto profil, dan pemulihan kata sandi melalui OTP belum tersedia.

Aturan Bisnis dan Keamanan
Saldo minimum yang mengendap saat penarikan adalah Rp10.000 secara default dan bisa diubah lewat konfigurasi.
Penarikan besar mulai Rp1.000.000 (default) memerlukan pemeriksaan konfirmasi.
Otorisasi nasabah menunggu persetujuan selama 5 menit.
Koreksi diajukan teller dan disetujui atau ditolak supervisor.
PIN disimpan dalam bentuk hash. Jangan pernah meminta atau mencatat PIN nasabah secara terbuka.
Akses web dibatasi oleh login dan role, sedangkan API dilindungi auth:sanctum.
Lisensi

Proyek ini dibangun menggunakan Laravel, framework open-source berlisensi MIT.
