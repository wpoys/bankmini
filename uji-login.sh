#!/usr/bin/env bash
# ============================================================
#  Uji Coba Otomatis Fitur LOGIN "Username / Email" (Blade)
#  Aplikasi : E-Teller Bank Mini Sekolah (Laravel)
#
#  Cara pakai (dari folder project):
#      bash uji-login.sh
#  atau double-click  uji-login.bat
#
#  Yang diuji:
#   A. Tampilan form login
#   B. Login via username & email (semua role)
#   C. Password salah, session, redirect role, RBAC
#   D. Lupa Password -> OTP 6 digit -> reset -> login password baru
#
#  Catatan: skenario D mengganti password sementara, lalu
#  otomatis dikembalikan ke wildan123 di akhir.
# ============================================================

set -u

BASE="http://127.0.0.1"
PORT=""
STARTED_SERVER=0
TMP_DIR=$(mktemp -d)
START_TS=$(php -r 'echo time();')
PASS=0
FAIL=0
WARN=0

cleanup() {
    rm -rf "$TMP_DIR"
    if [ "$STARTED_SERVER" = "1" ]; then
        netstat -ano 2>/dev/null | grep ":$PORT" | grep -i LISTENING | awk '{print $NF}' | sort -u \
            | while read -r pid; do taskkill //PID "$pid" //F >/dev/null 2>&1; done
        echo ">> Server pengujian (port $PORT) dihentikan."
    fi
}

restore_state() {
    export START_TS
    php artisan tinker --execute="
        \$u = App\Models\User::where('username', 'wildan')->first();
        if (\$u) { \$u->password = 'wildan123'; \$u->save(); echo 'akun wildan dikembalikan ke wildan123' . PHP_EOL; }
        Illuminate\Support\Facades\DB::table('password_reset_codes')->where('email', 'wildan@gmail.com')->delete();
        Illuminate\Support\Facades\DB::table('activity_logs')->where('created_at', '>=', date('Y-m-d H:i:s', (int) getenv('START_TS')))->delete();
        echo 'log aktivitas hasil pengujian dibersihkan' . PHP_EOL;
    "
    : > storage/logs/laravel.log
}

check() { # check <label> <1=ok 0=fail>
    if [ "$2" = "1" ]; then PASS=$((PASS+1)); printf "  %-58s PASS\n" "$1"; else FAIL=$((FAIL+1)); printf "  %-58s FAIL\n" "$1"; fi
}

warn() { # warn <label> (info yang tak bisa dicek otomatis, bukan kegagalan)
    WARN=$((WARN+1)); printf "  %-58s SKIP (lihat catatan)\n" "$1"; printf "    >> %s\n" "$2"
}

ensure_server() {
    for p in 8000 8001 8002; do
        if curl -s -o /dev/null "$BASE:$p/login" 2>/dev/null; then PORT=$p; echo ">> Memakai server yang sudah berjalan di port $p"; return 0; fi
    done
    for p in 8000 8001 8002; do
        if ! netstat -ano 2>/dev/null | grep ":$p" | grep -qi LISTENING; then
            echo ">> Menjalankan server: php artisan serve --port=$p"
            (php artisan serve --host=127.0.0.1 --port="$p" > "$TMP_DIR/serve.log" 2>&1 &)
            sleep 4
            if curl -s -o /dev/null "$BASE:$p/login" 2>/dev/null; then PORT=$p; STARTED_SERVER=1; return 0; fi
        fi
    done
    echo "!! Tidak ada server berjalan dan gagal menjalankan server." >&2
    echo "   Jalankan manual: php artisan serve" >&2
    exit 1
}

do_login() { # $1=jar-name $2=login $3=pass ; hasil redirect di $G_REDIR
    local ck="$TMP_DIR/$1.ck"
    curl -s -c "$ck" "$BASE:$PORT/login" -o "$TMP_DIR/$1.login.html"
    local T; T=$(grep -oP 'name="_token" value="\K[^"]+' "$TMP_DIR/$1.login.html" | head -1)
    G_REDIR=$(curl -s -b "$ck" -c "$ck" -X POST "$BASE:$PORT/login" -d "_token=$T&username=$2&password=$3" -o /dev/null -w "%{redirect_url}")
}

login_redirect() { # $1=label $2=jar $3=login $4=pass $5=expected-dash-suffix
    do_login "$2" "$3" "$4"
    case "$G_REDIR" in
        *"$5") check "$1" 1 ;;
        "$BASE:$PORT/login") check "$1" 0 ;;
        *) check "$1" 0 ;;
    esac
}

# ------------------------------------------------------------
trap cleanup EXIT
ensure_server

echo
echo "================= A. TAMPILAN FORM LOGIN ================="
HTML=$(curl -s "$BASE:$PORT/login")
case "$HTML" in *"Username / Email"*) check "Label 'Username / Email' tampil" 1;; *) check "Label 'Username / Email' tampil" 0;; esac
case "$HTML" in *"Masukkan username atau email"*) check "Hint 'Masukkan username atau email' tampil" 1;; *) check "Hint 'Masukkan username atau email' tampil" 0;; esac
case "$HTML" in *"Masukkan password"*) check "Field Password & hintnya tampil" 1;; *) check "Field Password & hintnya tampil" 0;; esac
case "$HTML" in *"> Masuk"*) check "Tombol 'Masuk' tampil" 1;; *) check "Tombol 'Masuk' tampil" 0;; esac
case "$HTML" in *"Lupa Password?"*) check "Tombol 'Lupa Password?' tampil" 1;; *) check "Tombol 'Lupa Password?' tampil" 0;; esac

echo
echo "================= B. LOGIN USERNAME & EMAIL ==============="
login_redirect "Login username admin/admin123"       j1 admin      admin123            admin/dashboard
login_redirect "Login email admin@bankmini.test"      j2 "admin@bankmini.test" admin123 admin/dashboard
login_redirect "Login username supervisor/super123"   j3 supervisor super123           supervisor/dashboard
login_redirect "Login email teller1@bankmini.test"    j4 "teller1@bankmini.test" teller123 teller/dashboard
login_redirect "Login username nasabah 20241001"      j5 20241001  123456             nasabah/dashboard
login_redirect "Login email wildan@gmail.com"         j6 "wildan@gmail.com" wildan123 nasabah/dashboard

echo
echo "================= C. PASSWORD, SESSION, RBAC =============="
do_login jfail wildan salahpass
case "$G_REDIR" in
    "$BASE:$PORT/login") check "Password salah ditolak (kembali ke /login)" 1 ;;
    *) check "Password salah ditolak (kembali ke /login)" 0 ;;
esac
FAILHTML=$(curl -s -b "$TMP_DIR/jfail.ck" "$BASE:$PORT/login")
case "$FAILHTML" in *"Username/email atau password salah."*) check "Pesan 'Username/email atau password salah.' tampil" 1;; *) check "Pesan 'Username/email atau password salah.' tampil" 0;; esac

# session bertahan: pakai sesi teller (j2 sudah login admin, pakai j4 teller)
SESS=$(curl -s -b "$TMP_DIR/j4.ck" -o /dev/null -w "%{http_code}" "$BASE:$PORT/teller/dashboard")
check "Session bertahan (akses /teller/dashboard = 200)" $([ "$SESS" = "200" ] && echo 1 || echo 0)

# RBAC blokir lintas role
RB=$(curl -s -b "$TMP_DIR/j4.ck" -o /dev/null -w "%{http_code}" "$BASE:$PORT/admin/dashboard")
check "RBAC: teller akses /admin/dashboard ditolak (403)" $([ "$RB" = "403" ] && echo 1 || echo 0)

# guest diblokir
GB=$(curl -s -o /dev/null -w "%{redirect_url}" "$BASE:$PORT/admin/dashboard")
case "$GB" in *"/login"*) check "Guest akses halaman terproteksi dialihkan ke login" 1;; *) check "Guest akses halaman terproteksi dialihkan ke login" 0;; esac

echo
echo "================= D. LUPA PASSWORD / OTP ================="
MAILER=$(grep -E "^MAIL_MAILER=" .env | cut -d= -f2 | tr -d '\r\n "') || "log"
printf "  (mailer aktif: %s)\n" "$MAILER"
: > storage/logs/laravel.log
ck="$TMP_DIR/fgt.ck"
curl -s -c "$ck" "$BASE:$PORT/forgot-password" -o "$TMP_DIR/fgt.html"
FT=$(grep -oP 'name="_token" value="\K[^"]+' "$TMP_DIR/fgt.html" | head -1)
GR=$(curl -s -b "$ck" -c "$ck" -X POST "$BASE:$PORT/forgot-password" -d "_token=$FT&login=wildan" -o /dev/null -w "%{redirect_url}")

if [[ "$GR" == *"/verify-otp"* ]]; then
    check "Input username 'wildan' -> halaman verifikasi OTP" 1
    BODY=$(curl -s -b "$ck" -c "$ck" "$GR")
    OTP=$(printf '%s' "$BODY" | grep -oP 'tracking-wider">\K[0-9]{6}' | head -1)
    [ -n "$OTP" ] && check "Kode OTP 6 digit tampil di layar (mode dev)" 1 || check "Kode OTP 6 digit tampil di layar (mode dev)" 0
    case "$BODY" in *"Kode OTP telah dikirim"*) check "Status 'kode terkirim' tampil di halaman verifikasi" 1;; *) check "Status 'kode terkirim' tampil di halaman verifikasi" 0;; esac

    if [ "$MAILER" = "log" ]; then
        MAILHIT=$(grep -oP '\b[0-9]{6}\b' storage/logs/laravel.log | grep -cF -e "$OTP")
        check "Kode OTP yang sama tercatat di email (laravel.log)" $([ "${MAILHIT:-0}" != 0 ] && echo 1 || echo 0)
    else
        warn "Pengiriman email via $MAILER (tersendir)" "Email dikirim ke inbox tujuan. Verifikasi inbox secara manual."
    fi
else
    check "Input username 'wildan' -> halaman verifikasi OTP" 0
    warn "OTP tidak terkirim / tidak sampai halaman verifikasi" "Jika mailer=smtp, pastikan MAIL_USERNAME & MAIL_PASSWORD (App Password) sudah diisi di .env, lalu jalankan: php artisan mail:test <email-tujuan>"
fi

if [ -n "${OTP:-}" ]; then
RT=$(printf '%s' "$BODY" | grep -oP 'name="_token" value="\K[^"]+' | head -1)
curl -s -b "$ck" -c "$ck" -X POST "$BASE:$PORT/verify-otp" -d "_token=$RT&email=wildan@gmail.com&code=000000&password=wildanOtp456&password_confirmation=wildanOtp456" -o /dev/null
ERRBODY=$(curl -s -b "$ck" -c "$ck" "$BASE:$PORT/verify-otp?email=wildan%40gmail.com")
case "$ERRBODY" in *"Kode OTP salah atau sudah kedaluwarsa."*) check "Kode OTP salah ditolak (pesan tampil)" 1;; *) check "Kode OTP salah ditolak (pesan tampil)" 0;; esac

# --- uji kirim ulang kode ---
GR2=$(curl -s -b "$ck" -c "$ck" -X POST "$BASE:$PORT/forgot-password" -d "_token=$FT&login=wildan" -o /dev/null -w "%{redirect_url}")
BODY2=$(curl -s -b "$ck" -c "$ck" "$GR2")
OTP2=$(printf '%s' "$BODY2" | grep -oP 'tracking-wider">\K[0-9]{6}' | head -1)
[ -n "$OTP2" ] && check "Kirim ulang kode OTP (resend)" 1 || check "Kirim ulang kode OTP (resend)" 0
[ -n "$OTP2" ] && [ "$OTP" != "$OTP2" ] && check "Kode baru berbeda dari kode sebelumnya" 1 || check "Kode baru berbeda dari kode sebelumnya" 0

# --- uji OTP kedaluwarsa: paksa kode kedaluwarsa lalu verifikasi ---
php artisan tinker --execute="
    Illuminate\Support\Facades\DB::table('password_reset_codes')->where('email', 'wildan@gmail.com')->update(['expires_at' => now()->subMinutes(5)]);
    echo 'expired';
" >/dev/null 2>&1
RT2=$(printf '%s' "$BODY2" | grep -oP 'name="_token" value="\K[^"]+' | head -1)
curl -s -b "$ck" -c "$ck" -X POST "$BASE:$PORT/verify-otp" -d "_token=$RT2&email=wildan@gmail.com&code=$OTP2&password=wildanOtp456&password_confirmation=wildanOtp456" -o /dev/null
EXPBODY=$(curl -s -b "$ck" -c "$ck" "$BASE:$PORT/forgot-password")
case "$EXPBODY" in *"kedaluwarsa"*) check "Kode OTP kedaluwarsa ditolak" 1;; *) check "Kode OTP kedaluwarsa ditolak" 0;; esac

# --- uji OTP benar dengan kode baru dari kirim ulang ---
GR3=$(curl -s -b "$ck" -c "$ck" -X POST "$BASE:$PORT/forgot-password" -d "_token=$FT&login=wildan" -o /dev/null -w "%{redirect_url}")
BODY3=$(curl -s -b "$ck" -c "$ck" "$GR3")
OTP3=$(printf '%s' "$BODY3" | grep -oP 'tracking-wider">\K[0-9]{6}' | head -1)
if [ -n "$OTP3" ]; then
    RT3=$(printf '%s' "$BODY3" | grep -oP 'name="_token" value="\K[^"]+' | head -1)
    RR=$(curl -s -b "$ck" -c "$ck" -X POST "$BASE:$PORT/verify-otp" -d "_token=$RT3&email=wildan@gmail.com&code=$OTP3&password=wildanOtp456&password_confirmation=wildanOtp456" -o /dev/null -w "%{redirect_url}")
    case "$RR" in *"/login"*) check "OTP benar -> password baru berhasil diset" 1;; *) check "OTP benar -> password baru berhasil diset" 0;; esac

    login_redirect "Login email dgn password baru (wildanOtp456)" j7 "wildan@gmail.com" wildanOtp456 nasabah/dashboard
    do_login j8 wildan wildan123
    case "$G_REDIR" in "$BASE:$PORT/login") check "Password lama ditolak setelah reset" 1;; *) check "Password lama ditolak setelah reset" 0;; esac
else
    warn "OTP valid tidak tersedia" "Uji 'OTP benar' dilewati; periksa kredensial SMTP di .env."
fi

HASH=$(php artisan tinker --execute="echo substr(App\Models\User::where('username','wildan')->first()->password, 0, 4);" 2>/dev/null)
case "$HASH" in "\$2y"*) check "Password tersimpan ter-hash (bcrypt)" 1;; *) check "Password tersimpan ter-hash (bcrypt)" 0;; esac
else
    warn "Verifikasi OTP dilewati" "Kode OTP tidak tersedia (pengiriman gagal/kredensial SMTP belum diisi). Jalankan 'php artisan mail:test <email>' untuk cek SMTP."
fi

# ------------------------------------------------------------
echo
echo ">> Pembersihan data hasil pengujian..."
restore_state

echo
echo "==========================================================="
echo "  HASIL UJI COBA"
echo "    PASS : $PASS"
echo "    FAIL : $FAIL"
if [ "$FAIL" = "0" ]; then
    echo "    STATUS : SEMUA LULUS"
else
    echo "    STATUS : ADA YANG GAGAL"
fi
echo "==========================================================="