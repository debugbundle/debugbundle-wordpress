<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class RelaySpool
{
    private const MAX_TOTAL_BYTES = 10485760;
    private const MAX_FILE_AGE_SECONDS = 86400;

    public function path(): string
    {
        $identity = $this->legacyPath();
        return rtrim(sys_get_temp_dir(), '/\\') . '/debugbundle-spool-' . substr(hash('sha256', $identity), 0, 16);
    }

    private function legacyPath(): string
    {
        if (function_exists('wp_upload_dir')) {
            $uploadDir = \wp_upload_dir();
            if (is_array($uploadDir) && isset($uploadDir['basedir']) && is_string($uploadDir['basedir'])) {
                return rtrim($uploadDir['basedir'], '/\\') . '/debugbundle-spool';
            }
        }

        return sys_get_temp_dir() . '/debugbundle-spool';
    }

    public function ensureDirectory(): bool
    {
        $path = $this->path();
        if (is_link($path)) {
            return false;
        }
        if (is_dir($path)) {
            if (!@chmod($path, 0700)) {
                return false;
            }
            $this->writeIndexFile($path);
            return true;
        }

        if (!function_exists('wp_mkdir_p') || (!\wp_mkdir_p($path) && !is_dir($path))) {
            return false;
        }

        if (!@chmod($path, 0700)) {
            return false;
        }
        $this->writeIndexFile($path);
        return true;
    }

    /** @param list<array<string, mixed>> $events */
    public function write(array $events): ?string
    {
        $events = Sanitization::protectRelayEvents($events);
        if ($events === null || $events === []) {
            return null;
        }
        if (!$this->ensureDirectory()) {
            return null;
        }

        try {
            $payload = json_encode(['events' => $events], JSON_THROW_ON_ERROR);
            $filePath = sprintf('%s/%d-%s.events.json', $this->path(), (int) floor(microtime(true) * 1000), bin2hex(random_bytes(4)));
        } catch (\Throwable) {
            return null;
        }
        if (strlen($payload) > 262144 || !$this->writePrivateFile($filePath, $payload)) {
            return null;
        }

        $this->prune();
        return $filePath;
    }

    /** @return list<array<string, mixed>>|null */
    public function readProtected(string $filePath): ?array
    {
        if (!in_array($filePath, $this->files(), true)) {
            return null;
        }
        try {
            $safe = $this->decodeProtectedFile($filePath);
            if ($safe === null || $safe === []) {
                return null;
            }
            $replacement = json_encode(['events' => $safe], JSON_THROW_ON_ERROR);
            if (strlen($replacement) > 262144) {
                return null;
            }
            $temporary = $filePath . '.' . bin2hex(random_bytes(6)) . '.tmp';
            if (!$this->writePrivateFile($temporary, $replacement)) {
                return null;
            }
            if (!@rename($temporary, $filePath)) {
                @unlink($temporary);
                return null;
            }
            return $safe;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Move bounded pre-upgrade uploads into private storage before any forwarding. */
    public function migrateLegacy(int $limit = 25): void
    {
        $legacy = $this->legacyPath();
        if ($legacy === $this->path() || is_link($legacy) || !is_dir($legacy)) {
            return;
        }
        $files = glob($legacy . '/*.events.json');
        if ($files === false) {
            return;
        }
        sort($files);
        foreach (array_slice($files, 0, max(0, min($limit, 25))) as $oldFile) {
            $safe = is_link($oldFile) ? null : $this->decodeProtectedFile($oldFile);
            if ($safe !== null) {
                if ($this->write($safe) === null) {
                    Diagnostics::recordRelayError('relay_privacy_migration_failed');
                }
            }
            // An unsafe or failed migration must never leave raw retry bytes under uploads.
            if (!@unlink($oldFile)) {
                Diagnostics::recordRelayError('relay_privacy_migration_failed');
            }
        }
    }

    /** @return list<array<string, mixed>>|null */
    private function decodeProtectedFile(string $filePath): ?array
    {
        if (is_link($filePath)) {
            return null;
        }
        $size = @filesize($filePath);
        if (!is_int($size) || $size < 1 || $size > 262144) {
            return null;
        }
        $contents = @file_get_contents($filePath, false, null, 0, 262145);
        if (!is_string($contents) || strlen($contents) !== $size) {
            return null;
        }
        try {
            $decoded = json_decode($contents, true, 17, JSON_THROW_ON_ERROR);
            return is_array($decoded) && isset($decoded['events']) && is_array($decoded['events'])
                ? Sanitization::protectRelayEvents($decoded['events']) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function writePrivateFile(string $path, string $content): bool
    {
        $handle = @fopen($path, 'xb');
        if ($handle === false) {
            return false;
        }
        try {
            if (!@chmod($path, 0600)) {
                @unlink($path);
                return false;
            }
            $written = @fwrite($handle, $content);
            if ($written !== strlen($content) || !@fflush($handle)) {
                @unlink($path);
                return false;
            }
            return true;
        } finally {
            fclose($handle);
        }
    }

    /** @return list<string> */
    public function files(): array
    {
        $path = $this->path();
        if (!is_dir($path)) {
            return [];
        }

        $files = glob($path . '/*.events.json');
        if ($files === false) {
            return [];
        }

        sort($files);
        return array_values(array_filter($files, 'is_string'));
    }

    public function delete(string $filePath): void
    {
        if (dirname($filePath) === $this->path() && str_ends_with($filePath, '.events.json') && function_exists('wp_delete_file')) {
            \wp_delete_file($filePath);
        }
    }

    /** @return array{count:int,size:int} */
    public function stats(): array
    {
        $files = $this->files();
        $size = 0;
        foreach ($files as $file) {
            $candidate = @filesize($file);
            if (is_int($candidate)) {
                $size += $candidate;
            }
        }

        return [
            'count' => count($files),
            'size' => $size,
        ];
    }

    public function prune(): void
    {
        $files = $this->files();
        $now = time();

        foreach ($files as $file) {
            $mtime = @filemtime($file);
            if (!is_int($mtime) || ($now - $mtime) > self::MAX_FILE_AGE_SECONDS) {
                $this->delete($file);
            }
        }

        $files = $this->files();
        $totalSize = 0;
        foreach ($files as $file) {
            $size = @filesize($file);
            if (is_int($size)) {
                $totalSize += $size;
            }
        }

        while ($totalSize > self::MAX_TOTAL_BYTES && $files !== []) {
            $oldest = array_shift($files);
            if (!is_string($oldest)) {
                break;
            }

            $size = @filesize($oldest);
            if (is_int($size)) {
                $totalSize -= $size;
            }
            $this->delete($oldest);
        }
    }

    private function writeIndexFile(string $directory): void
    {
        $indexFile = $directory . '/index.php';
        if (!is_file($indexFile)) {
            @file_put_contents($indexFile, "<?php\n// Silence is golden.\n");
        }

        $htaccessFile = $directory . '/.htaccess';
        if (!is_file($htaccessFile)) {
            @file_put_contents($htaccessFile, "Require all denied\nDeny from all\n");
        }
    }
}
