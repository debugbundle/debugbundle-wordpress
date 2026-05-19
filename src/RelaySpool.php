<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class RelaySpool
{
    private const MAX_TOTAL_BYTES = 10485760;
    private const MAX_FILE_AGE_SECONDS = 86400;

    public function path(): string
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
        if (is_dir($path)) {
            $this->writeIndexFile($path);
            return true;
        }

        if (!@mkdir($path, 0700, true) && !is_dir($path)) {
            return false;
        }

        @chmod($path, 0700);
        $this->writeIndexFile($path);
        return true;
    }

    /** @param list<array<string, mixed>> $events */
    public function write(array $events): ?string
    {
        if (!$this->ensureDirectory()) {
            return null;
        }

        $payload = json_encode(['events' => $events], JSON_THROW_ON_ERROR);
        $basePath = $this->path();
        $filePath = sprintf('%s/%d-%s.events.json', $basePath, (int) floor(microtime(true) * 1000), bin2hex(random_bytes(4)));
        if (@file_put_contents($filePath, $payload, LOCK_EX) === false) {
            return null;
        }

        @chmod($filePath, 0600);
        $this->prune();
        return $filePath;
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
        if (str_starts_with($filePath, $this->path())) {
            @unlink($filePath);
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
    }
}
