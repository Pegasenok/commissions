<?php

namespace App\Http;

class CachedBinlistClient extends BinlistClient
{
    private const CACHE_DIR = 'var/binlist_cache';
    private const CACHE_TTL = 60*60*24; // 24 hours in seconds

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->ensureCacheDirExists();
    }

    public function lookupBin(string $bin): object
    {
        $cacheFile = $this->getCacheFilePath($bin);

        if ($this->isCacheValid($cacheFile)) {
            return $this->getFromCache($cacheFile);
        }

        try {
            $response = parent::lookupBin($bin);
        } catch (\Throwable $throwable) {
            // fallback with stale data if possible
            if ($this->isCacheAvailable($cacheFile)) {
                return $this->getFromCache($cacheFile);
            }
            throw $throwable;
        }
        $this->saveToCache($bin, $response);

        return $response;
    }

    private function getCacheFilePath(string $bin): string
    {
        return self::CACHE_DIR . '/' . $bin . '.json';
    }

    private function isCacheValid(string $cacheFile): bool
    {
        if (!file_exists($cacheFile)) {
            return false;
        }

        $modTime = filemtime($cacheFile);
        return (time() - $modTime) < self::CACHE_TTL;
    }

    private function isCacheAvailable(string $cacheFile): bool
    {
        return file_exists($cacheFile);
    }

    private function getFromCache(string $cacheFile): object
    {
        $content = file_get_contents($cacheFile);
        return json_decode($content);
    }

    private function saveToCache(string $bin, object $response): void
    {
        file_put_contents(
            $this->getCacheFilePath($bin),
            json_encode($response)
        );
    }

    private function ensureCacheDirExists(): void
    {
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
    }
}
