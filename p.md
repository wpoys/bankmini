# Spesifikasi Kebutuhan Perangkat Lunak
## BANK MINI — Sistem Informasi E-Teller Sekolah

**Versi:** 1.0 (diselaraskan dengan implementasi repository)  
**Disiapkan oleh:** Muhammad Wildan Faqih Syahputra  
**Instansi:** SMK Negeri 1 Bantul  
**Tanggal:** November 2026

> Dokumen ini merangkum kebutuhan yang dapat dicocokkan dengan kode sumber BANK MINI. Backend yang tersedia dalam repository adalah aplikasi Laravel berbasis web dan REST API nasabah. Source Flutter tidak terdapat pada repository ini, sehingga kebutuhan khusus aplikasi Flutter dianggap belum terverifikasi dan bukan klaim fitur yang telah diimplementasikan. Kode sumber menjadi rujukan implementasi; konfigurasi runtime dan pengujian tetap perlu diverifikasi pada lingkungan deployment.

## Riwayat Perubahan

| Nama | Tanggal | Alasan | Versi |
|---|---|---|---|
| Muhammad Wildan Faqih Syahputra | 01 November 2026 | Dokumen awal SRS yang diselaraskan dengan program BANK MINI | 1.0 |

## 1. Pendahuluan

### 1.1 Tujuan
Dokumen ini menjadi acuan kebutuhan, pengembangan, pengujian, dan pemeliharaan BANK MINI. Dokumen membedakan fungsi yang tersedia dalam kode dari fungsi yang belum dapat diverifikasi agar tidak menjanjikan kemampuan yang belum terbukti.

### 1.2 Definisi

| Istilah | Definisi |
|---|---|
| BANK MINI | Sistem sekolah untuk pengelolaan rekening tabungan dan transaksi nasabah. |
| Nasabah | Pemilik rekening, umumnya siswa atau warga sekolah. |
| Admin | Pengelola pengguna, nasabah, konfigurasi, backup, dan proses administratif. |
| Teller | Petugas yang memproses transaksi, laporan kas, dan pengajuan koreksi. |
| Supervisor | Petugas yang memeriksa laporan, koreksi, dan laporan keuangan. |
| Sanctum | Mekanisme token autentikasi API Laravel. |
| PIN | PIN keamanan enam digit nasabah; disimpan sebagai hash. |
| Koreksi | Pengajuan pembalikan/koreksi transaksi yang melalui persetujuan. |
| TBD | Hal yang belum dipastikan atau belum dapat dibuktikan dari repository. |

### 1.3 Pembaca
Pengembang, analis, penguji, pengelola sekolah, Admin, Teller, Supervisor, serta pihak yang menyetujui ruang lingkup sistem.

### 1.4 Ruang Lingkup
Sistem yang dapat diverifikasi terdiri dari aplikasi web Laravel untuk Admin, Teller, dan Supervisor, serta REST API Laravel untuk nasabah. Cakupan mencakup autentikasi, data pengguna dan nasabah, transaksi, persetujuan nasabah atas transaksi tertentu, laporan kas dan keuangan, koreksi, verifikasi, jadwal, notifikasi, permintaan PIN, backup, dan ekspor laporan.

Tidak ditemukan source Flutter/Dart pada repository ini. Aplikasi mobile native, SSO sekolah, pembayaran online, transfer antar rekening, dan integrasi payment gateway tidak dinyatakan sebagai fungsi terimplementasi.

### 1.5 Penomoran
Kebutuhan fungsional menggunakan REQ-[MODUL]-[nomor], non-fungsional NFR-[kategori]-[nomor], dan aturan bisnis BR-[nomor].

### 1.6 Referensi
Kode sumber Laravel BANK MINI, khususnya `routes/web.php`, `routes/api.php`, controller, model, service/support, dan migrasi basis data. Dokumentasi resmi Laravel, Sanctum, PHP, dan MySQL digunakan sebagai referensi teknologi.

## 2. Deskripsi Umum

### 2.1 Perspektif Produk
Aplikasi menggunakan Laravel dengan basis data relasional. Web dan API mengakses data melalui backend Laravel; API nasabah menggunakan token Sanctum. Basis data menjadi sumber data bersama. Ketersediaan deployment, mail server, serta konfigurasi keamanan bergantung pada lingkungan produksi.

### 2.2 Fungsi Utama
- Login/logout web dan API, pembatasan role dan status akun.
- Pengelolaan staf dan nasabah, termasuk pengarsipan/pemulihan serta impor nasabah.
- Transaksi setoran/penarikan dan alur otorisasi transaksi nasabah melalui API.
- Pengajuan dan persetujuan koreksi transaksi.
- Laporan kas harian, rekonsiliasi, persetujuan laporan, verifikasi harian.
- Laporan keuangan dan ekspor PDF/XLSX sesuai modul yang tersedia.
- Jadwal petugas, notifikasi, pengajuan PIN, konfigurasi dan backup.
- API nasabah untuk dashboard, saldo, profil, mutasi, otorisasi transaksi, dan notifikasi.

### 2.3 Kelas Pengguna

| Peran | Fungsi |
|---|---|
| Admin | Pengelolaan data staf/nasabah, konfigurasi, backup, dan proses administratif; memiliki akses pada sejumlah modul review. |
| Teller | Pemrosesan transaksi, laporan kas, jadwal, dan pengajuan koreksi. |
| Supervisor | Review/approval laporan dan koreksi, audit, serta laporan keuangan. |
| Nasabah | Mengakses API untuk akun, saldo, profil, mutasi, otorisasi transaksi dan notifikasi. |

### 2.4 Lingkungan Operasi
Backend adalah Laravel 13.x/PHP 8.3 sebagaimana dinyatakan dalam konfigurasi/dokumen proyek, dengan MySQL sebagai basis data yang dituju. Web diakses melalui browser modern; API menggunakan JSON. Source Flutter tidak tersedia untuk memvalidasi persyaratan OS/perangkat mobile.

### 2.5 Batasan dan Asumsi
- Hak akses aktual mengikuti middleware dan route yang terpasang.
- Email OTP, HTTPS, backup, dan performa bergantung pada konfigurasi server.
- FCM disebut sebagai rencana/dukungan token perangkat, namun push aktif tidak diklaim tanpa verifikasi deployment.
- Angka kinerja/kapasitas dalam dokumen awal merupakan target, bukan hasil uji beban.

## 3. Antarmuka Eksternal

### 3.1 Antarmuka Pengguna
Web menyediakan antarmuka per role. API nasabah menyajikan data JSON untuk client. Rincian visual final bergantung pada antarmuka web yang ada; desain aplikasi Flutter tidak dapat diverifikasi dari repository.

### 3.2 Perangkat Keras
Komputer/browser untuk web dan server untuk Laravel/MySQL. API dapat diakses perangkat klien yang mampu melakukan HTTPS dan memproses JSON.

### 3.3 Antarmuka Perangkat Lunak
Laravel, Sanctum, basis data relasional, dan pustaka PDF/QR/XLSX yang digunakan proyek. Integrasi persis harus mengikuti `composer.json` dan konfigurasi deployment.

### 3.4 Komunikasi
Web memakai sesi Laravel; API memakai Bearer token Sanctum dan payload JSON. HTTPS wajib pada produksi. Endpoint login dan pemulihan akun dibatasi laju permintaannya.

## 4. Kebutuhan Fungsional

### 4.1 Autentikasi dan Otorisasi
- **REQ-AUT-01:** Sistem menyediakan login/logout web bagi staf dan membatasi akses dengan role serta status aktif.
- **REQ-AUT-02:** API mengautentikasi akun nasabah melalui Sanctum; akun nonaktif atau bukan nasabah tidak dapat menggunakan login nasabah.
- **REQ-AUT-03:** API menyediakan aktivasi akun dan pemulihan password dengan verifikasi kode/OTP sesuai alur backend.
- **REQ-AUT-04:** Endpoint autentikasi sensitif menerapkan throttling.
- **REQ-AUT-05:** Nasabah tidak dapat mengakses data transaksi milik nasabah lain.

### 4.2 Manajemen Pengguna dan Nasabah
- **REQ-MN-01:** Admin dapat mengelola akun staf melalui modul pengguna.
- **REQ-MN-02:** Admin dapat membuat, mengubah, mengarsipkan, memulihkan, dan menghapus data nasabah sesuai validasi aplikasi.
- **REQ-MN-03:** Sistem mendukung pencarian nasabah dan impor massal dengan pratinjau, konfirmasi, template, serta riwayat impor.
- **REQ-MN-04:** Akun nasabah, nomor rekening, PIN, dan status mengikuti validasi serta relasi yang diterapkan model dan migrasi.
- **REQ-MN-05:** Sistem menyediakan QR rekening apabila tersedia pada data/profil nasabah.

### 4.3 Transaksi dan Otorisasi Nasabah
- **REQ-TRX-01:** Teller dapat mencari nasabah dan mengajukan pemrosesan setoran/penarikan.
- **REQ-TRX-02:** Sistem memvalidasi status nasabah, nominal, PIN, dan aturan saldo sebelum transaksi dikomit.
- **REQ-TRX-03:** Transaksi dan perubahan terkait harus menjaga konsistensi data serta jurnal sesuai implementasi backend.
- **REQ-TRX-04:** Sistem menyediakan alur otorisasi transaksi nasabah melalui API: melihat, menyetujui, atau menolak permintaan yang menjadi miliknya.
- **REQ-TRX-05:** Sistem membatasi percobaan PIN dan menerapkan masa berlaku/kunci pada permintaan otorisasi sebagaimana konfigurasi backend.
- **REQ-TRX-06:** Sistem menyediakan struk transaksi untuk ditampilkan, dicetak, atau diunduh PDF melalui web.
- **REQ-TRX-07:** Sistem mencegah pemrosesan ganda sesuai mekanisme token/fingerprint yang diterapkan kode.

### 4.4 Koreksi Transaksi
- **REQ-KOR-01:** Teller dapat mengirim pengajuan koreksi dengan alasan.
- **REQ-KOR-02:** Supervisor dapat menyetujui atau menolak pengajuan koreksi.
- **REQ-KOR-03:** Persetujuan koreksi memproses perubahan/transaksi pembalik dan pencatatan terkait berdasarkan aturan service.
- **REQ-KOR-04:** Perubahan status dan tindakan koreksi tercatat sesuai mekanisme audit aplikasi.

### 4.5 Laporan Kas dan Verifikasi
- **REQ-KAS-01:** Teller dapat menyusun, melihat, dan mengirim laporan kas harian.
- **REQ-KAS-02:** Sistem mendukung rekonsiliasi kas berdasarkan data yang dimasukkan.
- **REQ-KAS-03:** Supervisor dapat meninjau laporan dan melakukan approve, reject, atau pending sesuai validasi.
- **REQ-KAS-04:** Admin atau Supervisor dapat melakukan verifikasi harian berdasarkan kewenangan route yang tersedia.
- **REQ-KAS-05:** Laporan dan verifikasi mengikuti batasan status serta aturan integritas backend.

### 4.6 Laporan Keuangan
- **REQ-LP-01:** Modul laporan menyediakan laporan operasional/harian, buku besar, jurnal, mutasi nasabah, saldo produk, laporan tahunan, dan kartu simpanan sesuai route yang tersedia.
- **REQ-LP-02:** Laporan mendukung filter yang disediakan masing-masing modul.
- **REQ-LP-03:** Sistem menyediakan ekspor/unduh PDF dan/atau XLSX pada laporan yang mendukungnya.
- **REQ-LP-04:** Akses laporan dibatasi berdasarkan role.

### 4.7 Jadwal Petugas
- **REQ-JP-01:** Admin dan Teller dapat mengakses fungsi pengelolaan jadwal sesuai route.
- **REQ-JP-02:** Supervisor dapat melihat jadwal.
- **REQ-JP-03:** Sistem memvalidasi bentrokan jadwal sesuai service jadwal.

### 4.8 PIN dan Notifikasi
- **REQ-PIN-01:** Nasabah dapat mengirim permintaan perubahan PIN melalui API; permintaan disimpan sebagai pengajuan, bukan langsung diterapkan.
- **REQ-PIN-02:** Nasabah dapat melihat status pengajuan PIN.
- **REQ-PIN-03:** Admin/Supervisor dapat memproses persetujuan atau penolakan pengajuan PIN melalui web.
- **REQ-NOT-01:** Pengguna terautentikasi dapat melihat notifikasi, menandai satu/semua telah dibaca, dan menghapus notifikasi sesuai hak kepemilikan.

### 4.9 API Nasabah
- **REQ-API-01:** API menyediakan dashboard, saldo, profil, serta informasi rekening nasabah yang sedang login.
- **REQ-API-02:** API menyediakan daftar/detail transaksi yang diotorisasi untuk nasabah tersebut, dengan filter dan paginasi.
- **REQ-API-03:** API mendukung keluaran mutasi/statement, termasuk PDF jika format tersebut diaktifkan.
- **REQ-API-04:** Respons API tidak boleh membocorkan PIN atau kredensial.
- **REQ-API-05:** API menyediakan pengelolaan notifikasi milik nasabah.

### 4.10 Konfigurasi, Backup, dan Audit
- **REQ-ADM-01:** Admin dapat mengelola konfigurasi yang disediakan modul konfigurasi.
- **REQ-ADM-02:** Admin dapat membuat, mengunduh, dan menghapus file backup melalui web.
- **REQ-ADM-03:** Sistem mencatat aktivitas penting melalui activity log sesuai implementasi.

## 5. Kebutuhan Non-Fungsional dan Aturan Bisnis

### 5.1 Kinerja
Target dashboard, transaksi, API, ekspor, dan jumlah pengguna simultan harus diuji pada server sasaran. Nilai target dari dokumen awal—misalnya API baca 500 ms, transaksi 2 detik, 50 pengguna bersamaan—belum dinyatakan sebagai hasil terukur.

### 5.2 Keamanan
- Password/PIN disimpan sebagai hash dan tidak dikirim sebagai data keluaran.
- Semua operasi memeriksa autentikasi, role, status akun, dan kepemilikan data.
- Validasi input dilakukan pada backend; form web memakai perlindungan CSRF Laravel.
- Produksi harus menggunakan HTTPS dan kredensial database berhak minimum.
- Endpoint sensitif menerapkan throttling.

### 5.3 Integritas dan Ketersediaan
Transaksi keuangan harus atomik, nominal valid, saldo mengikuti batas bisnis, relasi basis data dijaga, dan jurnal mengikuti mekanisme pembukuan aplikasi. Ketersediaan, backup berkala, pemulihan, uptime, serta pemrosesan asinkron perlu dibuktikan pada deployment dan tidak diasumsikan dari keberadaan route saja.

### 5.4 Usability dan Pemeliharaan
Antarmuka web menggunakan bahasa Indonesia. Format uang/tanggal dan responsivitas perlu diverifikasi terhadap tampilan aktual. Kode mengikuti pola Laravel yang digunakan proyek; pengujian otomatis harus dijalankan sebelum rilis.

### 5.5 Aturan Bisnis
- Saldo nasabah tidak boleh negatif dan nominal transaksi harus positif.
- Penarikan tunduk pada saldo tersedia dan batas saldo minimum yang dikonfigurasi.
- PIN nasabah wajib diverifikasi pada alur yang mewajibkannya.
- Nasabah nonaktif/arsip tidak boleh melakukan transaksi.
- Koreksi memerlukan persetujuan dan meninggalkan jejak transaksi.
- Pengajuan PIN berlaku setelah disetujui.
- Jadwal petugas tidak boleh bentrok bila validasi service menolaknya.
- Akses data API nasabah dibatasi pada pemilik akun.

## 6. Data dan Operasional
Entitas yang teridentifikasi dari model/migrasi mencakup users, nasabah, transaksi, jurnal akuntansi, laporan harian dan detailnya, rekonsiliasi kas dan detailnya, koreksi transaksi, verifikasi harian, permintaan reset PIN, notifikasi, jadwal petugas, konfigurasi, sequence nomor, activity log, batch impor nasabah, token Sanctum, serta tabel infrastruktur Laravel. Nama kolom dan constraint final mengikuti migrasi aktif.

Pengujian minimum meliputi autentikasi dan role, manajemen nasabah, transaksi/saldo/jurnal, otorisasi nasabah, koreksi, laporan/verifikasi, permintaan PIN, notifikasi, backup, dan pembatasan kepemilikan API. Jalankan test suite yang tersedia pada repository sebelum rilis.

## Appendix A — Hal yang Perlu Diverifikasi
1. Source aplikasi Flutter tidak tersedia dalam repository ini; seluruh klaim UI, biometrik, preferensi lokal, kompatibilitas Android/iOS, dan push notification mobile belum diverifikasi.
2. Nilai kinerja, kapasitas simultan, uptime, serta hasil uji beban perlu diuji pada infrastruktur produksi.
3. Konfigurasi SMTP/OTP, TLS/HTTPS, backup dan restore perlu diuji pada server target.
4. Aturan saldo minimum, batas penarikan, H-1, jurnal koreksi, dan status verifikasi perlu dicocokkan dengan konfigurasi aktif dan test suite.
5. Tabel atau fitur yang hanya muncul dalam migrasi historis tidak otomatis berarti fitur aktif; rujukan utamanya adalah route, controller, service, dan skema migrasi yang berlaku.
