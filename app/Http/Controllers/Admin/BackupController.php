<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    protected string $dir;

    public function __construct()
    {
        $this->dir = storage_path('app/backups');
    }

    public function store(): RedirectResponse
    {
        $this->ensureDirectory();

        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");
        $database = config("database.connections.{$connection}.database");

        $result = $driver === 'sqlite'
            ? $this->copySqliteFile($database)
            : $this->dumpMysql($connection, $database);

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', 'Backup berhasil dibuat: '.$result['name']);
    }

    public function download(string $file): BinaryFileResponse
    {
        $path = $this->resolve($file);

        if (! $path) {
            abort(404, 'Backup tidak ditemukan.');
        }

        return response()->download($path);
    }

    public function destroy(string $file): RedirectResponse
    {
        $path = $this->resolve($file);

        if (! $path) {
            return back()->with('error', 'Backup tidak ditemukan.');
        }

        File::delete($path);

        return back()->with('success', 'Backup berhasil dihapus.');
    }

    protected function dumpMysql(string $connection, mixed $database): array
    {
        if (! is_string($database) || $database === '') {
            return ['error' => 'Nama basis data tidak terkonfigurasi, backup tidak dapat dibuat.'];
        }

        $binary = $this->resolveBinary('mysqldump');

        if (! $binary) {
            return ['error' => 'Utilitas mysqldump tidak ditemukan. Isi MYSQLDUMP_PATH di .env dengan lokasi mysqldump.'];
        }

        $host = config("database.connections.{$connection}.host", '127.0.0.1');
        $port = (string) config("database.connections.{$connection}.port", '3306');
        $username = (string) config("database.connections.{$connection}.username", 'root');
        $password = (string) config("database.connections.{$connection}.password", '');

        $process = Process::env(['MYSQL_PWD' => $password])->run([
            $binary,
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--no-tablespaces',
            $database,
        ]);

        if (! $process->successful()) {
            $detail = trim($process->errorOutput()) ?: trim($process->output());

            return [
                'error' => 'Gagal menjalankan mysqldump'.($detail !== '' ? ': '.$detail : '.').' Pastikan MYSQLDUMP_PATH mengarah ke mysqldump yang benar.',
            ];
        }

        $name = 'bankmini-'.now()->format('Y-m-d-Hi').'-'.substr((string) uniqid(), 0, 8).'.sql';

        if (File::put($this->dir.'/'.$name, $process->output()) === false) {
            return ['error' => 'Gagal menyimpan hasil backup.'];
        }

        return ['name' => $name];
    }

    protected function copySqliteFile(mixed $dbPath): array
    {
        if (! is_string($dbPath) || $dbPath === ':memory:') {
            return ['error' => 'Database tidak disimpan sebagai file (in-memory), backup tidak dapat dibuat.'];
        }

        $source = $this->resolvePath($dbPath);

        if (! is_string($source) || ! is_file($source)) {
            return ['error' => 'File database tidak ditemukan, backup tidak dapat dibuat.'];
        }

        $name = 'bankmini-'.now()->format('Y-m-d-Hi').'-'.substr((string) uniqid(), 0, 8).'.sqlite';

        if (! File::copy($source, $this->dir.'/'.$name)) {
            return ['error' => 'Gagal membuat backup.'];
        }

        return ['name' => $name];
    }

    protected function resolveBinary(string $name): ?string
    {
        $configured = env('MYSQLDUMP_PATH');

        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [];

        if (DIRECTORY_SEPARATOR === '\\') {
            $candidates = array_merge(
                glob('C:/*laragon*/bin/mysql/*/bin/'.$name.'.exe') ?: [],
                glob('C:/Program Files/MySQL/*/bin/'.$name.'.exe') ?: [],
                [
                    'C:/xampp/mysql/bin/'.$name.'.exe',
                ],
            );
        } else {
            $candidates = ['/usr/bin/'.$name, '/usr/local/bin/'.$name, '/opt/lampp/bin/'.$name];
        }

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && is_file($candidate)) {
                return $candidate;
            }
        }

        return $name;
    }

    protected function resolve(string $file): ?string
    {
        $basename = basename($file);

        if ($basename !== $file || preg_match('/^[\w.-]+\.(sql|sqlite)$/', $basename) !== 1) {
            return null;
        }

        $path = $this->dir.'/'.$basename;

        return File::exists($path) ? $path : null;
    }

    protected function resolvePath(string $dbPath): ?string
    {
        if ($dbPath === ':memory:') {
            return null;
        }

        if (str_starts_with($dbPath, '/') || str_starts_with($dbPath, '\\') || preg_match('/^[A-Za-z]:[\\\\\/]/', $dbPath) === 1) {
            return $dbPath;
        }

        return base_path($dbPath);
    }

    protected function ensureDirectory(): void
    {
        if (! File::exists($this->dir)) {
            File::makeDirectory($this->dir, 0755, true);
        }
    }
}
