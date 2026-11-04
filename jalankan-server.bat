@echo off
rem Jalankan server Laravel di jendela tersendiri.
rem Server aktif selama jendela ini terbuka.
rem Host 0.0.0.0 agar bisa diakses emulator Android (10.0.2.2) DAN
rem HP fisik pada Wi-Fi yang sama (http://<IP-PC>:8000).
rem Aplikasi dapat diakses di http://127.0.0.1:8000
pushd "%~dp0"
start "Bank Mini Server (http://0.0.0.0:8000)" cmd /k "php artisan serve --host=0.0.0.0 --port=8000"
echo Server sedang dinyalakan...
echo Buka http://127.0.0.1:8000/login  di browser Anda.
echo HP fisik : flutter run --dart-define=API_BASE_URL=http://^<IP-PC^>:8000/api
echo Catatan  : bila HP fisik tetap gagal, izinkan PHP di Windows Firewall
pause