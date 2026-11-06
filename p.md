# Activity Diagram Sistem Bank Mini

Diagram ini merangkum alur yang diterapkan pada aplikasi: autentikasi staf, transaksi teller, laporan kas harian, koreksi, verifikasi supervisor, administrasi, serta layanan nasabah.

## 1. Login staf

```mermaid
flowchart TD
    A([Mulai]) --> B[Masukkan username atau email dan password]
    B --> C{Data login valid?}
    C -->|Tidak| B
    C -->|Ya| D{Akun aktif?}
    D -->|Tidak| B
    D -->|Ya| E{Akun nasabah?}
    E -->|Ya| F[Login ditolak: nasabah menggunakan E-Teller]
    F --> B
    E -->|Tidak| G[Buat sesi login]
    G --> H{Peran pengguna}
    H -->|Admin| I[Dashboard admin]
    H -->|Teller| J[Dashboard teller]
    H -->|Supervisor| K[Dashboard supervisor]
    I --> L{Pilih menu}
    J --> L
    K --> L
    L -->|Keluar| M[Hapus sesi dan logout]
    M --> N([Selesai])
    L -->|Lanjut| L
```

## 2. Transaksi teller

```mermaid
flowchart TD
    A([Mulai]) --> B[Buka menu transaksi]
    B --> C[Masukkan nomor rekening, NIS, atau pindai QR]
    C --> D[Cari data nasabah]
    D --> E{Nasabah ditemukan, aktif, dan tidak diarsipkan?}
    E -->|Tidak| C
    E -->|Ya| F[Tampilkan data dan saldo nasabah]
    F --> G[Pilih setoran atau penarikan]
    G --> H[Masukkan jumlah, PIN 6 digit, dan catatan opsional]
    H --> I{Formulir dan PIN valid?}
    I -->|Tidak| H
    I -->|Ya| J{Token formulir pernah dipakai?}
    J -->|Ya| K[Buka bukti transaksi yang sudah tercatat]
    K --> Z([Selesai])
    J -->|Tidak| L{Transaksi duplikat terdeteksi?}
    L -->|Ya| K
    L -->|Tidak| M{Jenis transaksi}
    M -->|Setoran| Q[Hitung saldo setelah setoran]
    M -->|Penarikan| N{Saldo mencukupi?}
    N -->|Tidak| H
    N -->|Ya| O{Saldo setelah penarikan memenuhi saldo minimum?}
    O -->|Tidak| H
    O -->|Ya| P{Melebihi batas penarikan besar?}
    P -->|Ya| R{Konfirmasi H-1 diberikan?}
    R -->|Tidak| H
    R -->|Ya| S[Lanjutkan transaksi]
    P -->|Tidak| S
    Q --> T[Simpan transaksi berstatus disetujui]
    S --> T
    T --> U[Perbarui saldo nasabah]
    U --> V[Buat nomor bukti dan catatan jurnal]
    V --> W[Catat aktivitas dan kirim notifikasi ke nasabah]
    W --> X[Tampilkan atau cetak bukti transaksi]
    X --> Z
```

## 3. Laporan kas harian teller

```mermaid
flowchart TD
    A([Mulai]) --> B[Buka laporan harian]
    B --> C[Hitung transaksi disetujui hari ini]
    C --> D[Hitung saldo awal dan kas akhir sistem: saldo awal + setoran - penarikan]
    D --> E[Masukkan kas fisik akhir, rincian pecahan opsional, dan kas ke brankas]
    E --> F{Data laporan valid dan kas brankas tidak melebihi kas sistem?}
    F -->|Tidak| E
    F -->|Ya| G{Laporan hari ini sudah disetujui atau ditolak?}
    G -->|Ya| H[Tidak dapat mengubah laporan]
    H --> Z([Selesai])
    G -->|Tidak| I[Simpan laporan dan selisih kas fisik terhadap sistem]
    I --> J{Ada selisih?}
    J -->|Ya| K[Tampilkan peringatan selisih]
    J -->|Tidak| L[Tampilkan laporan tersimpan]
    K --> M{Kirim untuk persetujuan?}
    L --> M
    M -->|Tidak| Z
    M -->|Ya| N{Milik teller ini dan status draft atau pending?}
    N -->|Tidak| Z
    N -->|Ya| O[Ubah status menjadi menunggu persetujuan]
    O --> P[Catat waktu pengajuan dan aktivitas]
    P --> Q[Beritahu supervisor aktif]
    Q --> Z
```

## 4. Pengajuan dan verifikasi koreksi transaksi

```mermaid
flowchart TD
    A([Mulai]) --> B[Teller membuka daftar transaksi yang dapat dikoreksi]
    B --> C{Pilih transaksi asli?}
    C -->|Ya| D[Pilih transaksi milik teller yang disetujui dan belum dikoreksi]
    C -->|Tidak| E[Ajukan koreksi tanpa transaksi asli]
    D --> F[Masukkan alasan koreksi dan data transaksi pembalik]
    E --> F
    F --> G{Data valid, nasabah sesuai, belum ada koreksi duplikat, dan arah koreksi benar?}
    G -->|Tidak| F
    G -->|Ya| H[Simpan pengajuan berstatus menunggu]
    H --> I[Catat aktivitas dan beri notifikasi supervisor]
    I --> J[Supervisor membuka pengajuan]
    J --> K{Status masih menunggu dan kode verifikasi diwajibkan?}
    K -->|Ya| L{Kode verifikasi benar?}
    L -->|Tidak| J
    L -->|Ya| M{Keputusan supervisor}
    K -->|Tidak| M
    M -->|Tolak| N[Masukkan alasan penolakan]
    N --> O[Simpan status ditolak dan beri tahu teller]
    O --> Z([Selesai])
    M -->|Setujui| P[Periksa saldo dan data koreksi]
    P --> Q{Koreksi valid dan saldo mencukupi?}
    Q -->|Tidak| J
    Q -->|Ya| R[Buat transaksi koreksi dan perbarui saldo serta jurnal]
    R --> S[Tandai transaksi asli sudah dikoreksi, jika ada]
    S --> T[Simpan status disetujui, catat aktivitas, dan beri tahu teller]
    T --> Z
```

## 5. Persetujuan dan verifikasi supervisor

```mermaid
flowchart TD
    A([Mulai]) --> B[Admin atau supervisor membuka pusat verifikasi]
    B --> C{Pilih antrean}
    C -->|Laporan kas| D[Buka laporan berstatus menunggu persetujuan]
    D --> E{Selisih kas tidak nol?}
    E -->|Ya| F[Persetujuan ditolak; pilih pending atau tolak dengan catatan]
    F --> G{Keputusan}
    G -->|Pending| H[Simpan status pending]
    G -->|Tolak| I[Simpan status ditolak dan alasan]
    E -->|Tidak| J{Setujui, pending, atau tolak?}
    J -->|Setujui| K[Simpan status disetujui dan data pemeriksa]
    K --> L{Ada kas dipindahkan ke brankas?}
    L -->|Ya| M[Catat pemindahan dan jurnal kas]
    L -->|Tidak| N[Beritahu teller]
    M --> N
    J -->|Pending| H
    J -->|Tolak| I
    H --> N
    I --> N
    C -->|Verifikasi transaksi harian| O[Pilih tanggal dan tinjau transaksi serta ringkasan]
    O --> P{Hari ini sudah disetujui atau tidak ada transaksi?}
    P -->|Ya| Q[Tolak proses verifikasi]
    P -->|Tidak| R{Keputusan diterima atau ditolak?}
    R -->|Ditolak| S[Masukkan catatan penolakan]
    R -->|Diterima| T[Simpan snapshot ringkasan dan status diterima]
    S --> U[Simpan status penolakan; tanggal dapat ditinjau ulang]
    T --> V[Beritahu teller terkait]
    U --> V
    V --> Z([Selesai])
    Q --> Z
    N --> Z
    C -->|Koreksi| W[Tinjau dan proses koreksi sesuai diagram koreksi]
    C -->|Permintaan PIN| X[Tinjau permintaan perubahan PIN]
    X --> Y{Setujui atau tolak?}
    Y -->|Setujui| AA[Validasi permintaan, perbarui PIN nasabah, dan tandai diproses]
    Y -->|Tolak| AB[Masukkan alasan; PIN lama tetap berlaku]
    AA --> Z
    AB --> Z
    C -->|Aktivitas| AC[Lihat log aktivitas terbaru]
    AC --> Z
    W --> Z
```

## 6. Portal dan aktivasi nasabah

```mermaid
flowchart TD
    A([Mulai]) --> B{Akun sudah aktif?}
    B -->|Belum| C[Ajukan kode aktivasi dengan NIS dan email]
    C --> D{NIS, email, peran nasabah, dan produk tabungan cocok?}
    D -->|Tidak| C
    D -->|Ya| E{Akun sudah aktif?}
    E -->|Ya| F[Informasikan akun sudah aktif]
    F --> Z([Selesai])
    E -->|Tidak| G[Kirim kode aktivasi]
    G --> H[Masukkan kode]
    H --> I{Akun terkunci karena percobaan gagal?}
    I -->|Ya| J[Tunggu masa kunci berakhir]
    J --> H
    I -->|Tidak| K{Kode valid dan belum kedaluwarsa?}
    K -->|Tidak| L[Tambah hitungan gagal]
    L --> M{Sudah 3 kali gagal?}
    M -->|Ya| N[Kunci aktivasi selama 1 jam]
    M -->|Tidak| H
    N --> Z
    K -->|Ya| O[Terbitkan token sementara 15 menit]
    O --> P[Masukkan password dan PIN 6 digit]
    P --> Q{Token, password, dan PIN memenuhi aturan?}
    Q -->|Tidak| P
    Q -->|Ya| R[Simpan password dan PIN terenkripsi serta aktifkan akun]
    R --> S[Terbitkan token akses]
    B -->|Ya| T[Masukkan kredensial untuk login]
    S --> U[Buka beranda nasabah]
    T --> V{Login berhasil dan akun aktif?}
    V -->|Tidak| T
    V -->|Ya| U
    U --> W[Lihat ringkasan saldo, transaksi, profil, dan notifikasi]
    W --> X{Pilih tindakan}
    X -->|Lihat detail atau riwayat| W
    X -->|Ubah PIN| Y[Masukkan PIN lama dan PIN baru]
    Y --> AA{PIN lama benar, PIN baru aman, dan tidak ada permintaan tertunda?}
    AA -->|Tidak| Y
    AA -->|Ya| AB[Simpan permintaan PIN dan beri tahu admin serta supervisor]
    AB --> W
    X -->|Keluar| Z
```

## 7. Administrasi nasabah dan impor

```mermaid
flowchart TD
    A([Mulai]) --> B[Admin membuka menu nasabah]
    B --> C{Pilih tindakan}
    C -->|Tambah atau ubah| D[Masukkan data nasabah]
    D --> E{Data valid?}
    E -->|Tidak| D
    E -->|Ya| F[Simpan data nasabah]
    C -->|Arsipkan| G[Pilih nasabah dan konfirmasi]
    G --> H[Arsipkan nasabah]
    C -->|Pulihkan| I[Pilih nasabah arsip]
    I --> J[Pulihkan nasabah]
    C -->|Impor| K[Unduh template dan unggah file]
    K --> L[Pratinjau serta validasi data impor]
    L --> M{Konfirmasi impor?}
    M -->|Tidak| N[Perbaiki file atau batalkan]
    N --> K
    M -->|Ya| O[Proses impor dan tampilkan hasil]
    F --> P([Selesai])
    H --> P
    J --> P
    O --> P
```

# Sequence Diagram Sistem Bank Mini

Diagram sequence berikut menggambarkan pertukaran pesan antaraktor, aplikasi, dan penyimpanan data pada alur inti sistem.

## 1. Login staf

```mermaid
sequenceDiagram
    actor Staf as Admin/Teller/Supervisor
    participant UI as Halaman Login
    participant Auth as LoginController
    participant DB as Database
    participant Session as Sesi Aplikasi

    Staf->>UI: Masukkan username/email dan password
    UI->>Auth: Kirim kredensial
    Auth->>DB: Cari pengguna berdasarkan username/email
    DB-->>Auth: Data pengguna
    alt Kredensial salah
        Auth-->>UI: Tampilkan pesan gagal
    else Akun tidak aktif
        Auth-->>UI: Tampilkan pesan akun tidak aktif
    else Pengguna adalah nasabah
        Auth-->>UI: Arahkan ke login E-Teller
    else Kredensial valid dan staf aktif
        Auth->>Session: Autentikasi dan regenerasi sesi
        Auth->>DB: Catat aktivitas login
        Auth-->>UI: Arahkan ke dashboard sesuai peran
        UI-->>Staf: Tampilkan dashboard
    end
```

## 2. Transaksi teller

```mermaid
sequenceDiagram
    actor Teller
    participant UI as Halaman Transaksi
    participant TC as TransaksiController
    participant QR as QrisNasabah
    participant DB as Database
    participant Journal as Jurnal Transaksi
    participant Notify as Notifikasi

    Teller->>UI: Cari nasabah (rekening/NIS/QR)
    UI->>TC: Permintaan pencarian
    TC->>QR: Cari nasabah berdasarkan identitas
    QR->>DB: Ambil data rekening/nasabah
    DB-->>QR: Data nasabah
    QR-->>TC: Hasil pencarian
    alt Tidak ditemukan/tidak aktif/diarsipkan
        TC-->>UI: Tampilkan pesan nasabah tidak dapat dilayani
    else Nasabah layak dilayani
        TC-->>UI: Tampilkan profil dan saldo
        Teller->>UI: Pilih setoran/penarikan, jumlah, PIN, catatan
        UI->>TC: Kirim transaksi beserta token formulir
        TC->>DB: Periksa token transaksi sebelumnya
        alt Token sudah pernah dipakai
            DB-->>TC: Transaksi sebelumnya ditemukan
            TC-->>UI: Arahkan ke bukti transaksi sebelumnya
        else Token baru
            TC->>DB: Periksa duplikasi transaksi
            alt Duplikat terdeteksi
                DB-->>TC: Transaksi duplikat ditemukan
                TC-->>UI: Tampilkan bukti transaksi sebelumnya
            else Transaksi baru
                TC->>DB: Validasi nasabah, PIN, saldo dan batas penarikan
                alt Validasi gagal
                    DB-->>TC: Validasi ditolak
                    TC-->>UI: Tampilkan alasan dan minta koreksi input
                else Validasi berhasil
                    TC->>DB: Mulai transaksi database
                    TC->>DB: Simpan transaksi berstatus disetujui
                    TC->>DB: Perbarui saldo nasabah
                    TC->>Journal: Catat entri jurnal
                    TC->>DB: Catat aktivitas
                    TC->>DB: Commit transaksi database
                    TC->>Notify: Kirim notifikasi transaksi ke nasabah
                    TC-->>UI: Tampilkan nomor bukti transaksi
                    UI-->>Teller: Tampilkan/cetak bukti
                end
            end
        end
    end
```

## 3. Pengajuan laporan kas harian

```mermaid
sequenceDiagram
    actor Teller
    participant UI as Halaman Laporan
    participant LC as LaporanController
    participant DB as Database
    participant Notify as Notifikasi

    Teller->>UI: Buka laporan hari ini
    UI->>LC: Minta ringkasan kas
    LC->>DB: Ambil transaksi disetujui hari ini dan saldo awal
    DB-->>LC: Transaksi, setoran, dan penarikan
    LC-->>UI: Tampilkan kas awal dan kas akhir sistem
    Teller->>UI: Isi kas fisik, pecahan, dan kas ke brankas
    UI->>LC: Simpan laporan
    LC->>DB: Periksa kepemilikan, status, dan validasi nominal
    alt Laporan telah disetujui/ditolak atau data tidak valid
        DB-->>LC: Tolak perubahan
        LC-->>UI: Tampilkan alasan penolakan
    else Valid
        LC->>DB: Simpan laporan dan selisih kas
        DB-->>LC: Laporan tersimpan
        LC-->>UI: Tampilkan laporan dan peringatan selisih bila ada
        opt Teller mengirim untuk persetujuan
            Teller->>UI: Ajukan laporan
            UI->>LC: Submit laporan
            LC->>DB: Pastikan pemilik dan status draft/pending
            LC->>DB: Ubah status menjadi menunggu persetujuan
            LC->>DB: Catat aktivitas dan waktu pengajuan
            LC->>Notify: Beri tahu supervisor aktif
            LC-->>UI: Tampilkan status pengajuan
        end
    end
```

## 4. Pemeriksaan dan persetujuan supervisor

```mermaid
sequenceDiagram
    actor Supervisor as Supervisor/Admin
    participant UI as Pusat Verifikasi
    participant VC as VerifikasiController
    participant DB as Database
    participant Journal as Jurnal Kas
    participant Notify as Notifikasi

    Supervisor->>UI: Buka antrean laporan
    UI->>VC: Minta laporan menunggu persetujuan
    VC->>DB: Ambil laporan dan rincian kas
    DB-->>VC: Data laporan
    VC-->>UI: Tampilkan laporan
    Supervisor->>UI: Pilih setujui, pending, atau tolak
    UI->>VC: Kirim keputusan dan catatan
    VC->>DB: Periksa status dan selisih kas
    alt Laporan memiliki selisih dan diminta disetujui
        VC-->>UI: Tolak persetujuan; minta pending atau penolakan
    else Keputusan pending
        VC->>DB: Ubah status menjadi pending
        VC->>Notify: Beri tahu teller
        VC-->>UI: Tampilkan status pending
    else Keputusan ditolak
        VC->>DB: Simpan status ditolak dan alasan
        VC->>Notify: Beri tahu teller
        VC-->>UI: Tampilkan status ditolak
    else Disetujui tanpa selisih
        VC->>DB: Simpan status, pemeriksa, waktu, dan catatan
        opt Ada kas yang dipindahkan ke brankas
            VC->>DB: Catat pemindahan kas
            VC->>Journal: Catat jurnal pemindahan
        end
        VC->>Notify: Beri tahu teller
        VC-->>UI: Tampilkan status disetujui
    end
```

## 5. Nasabah mengajukan perubahan PIN

```mermaid
sequenceDiagram
    actor Nasabah
    participant App as Aplikasi E-Teller
    participant API as API Nasabah
    participant PIN as PermintaanPinService
    participant DB as Database
    participant Notify as Notifikasi Admin/Supervisor

    Nasabah->>App: Masukkan PIN lama dan PIN baru
    App->>API: Kirim permintaan dengan token autentikasi
    API->>PIN: Validasi dan proses pengajuan
    PIN->>DB: Ambil data nasabah dan permintaan PIN aktif
    DB-->>PIN: Data PIN dan status pengajuan
    alt PIN lama salah/PIN baru lemah atau sama/ada permintaan menunggu
        PIN-->>API: Tolak permintaan beserta alasan
        API-->>App: Tampilkan pesan validasi
    else Permintaan valid
        PIN->>DB: Simpan hash PIN baru sebagai permintaan menunggu
        PIN->>Notify: Beri tahu admin dan supervisor aktif
        PIN-->>API: Kembalikan status pengajuan
        API-->>App: Tampilkan konfirmasi pengajuan
    end
    opt Admin atau supervisor memproses permintaan
        actor Petugas as Admin/Supervisor
        Petugas->>API: Setujui atau tolak permintaan
        API->>PIN: Proses keputusan
        PIN->>DB: Kunci dan periksa permintaan yang masih menunggu
        alt Disetujui
            PIN->>DB: Terapkan hash PIN ke nasabah dan rekening
            PIN->>DB: Tandai permintaan diproses
        else Ditolak dengan alasan
            PIN->>DB: Tandai ditolak; PIN lama tetap berlaku
        end
        PIN-->>API: Hasil pemrosesan
        API-->>Petugas: Tampilkan hasil keputusan
    end
```
