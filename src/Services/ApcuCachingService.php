<?php

namespace Caching\Services;

use Caching\Exceptions\CachingException;

class ApcuCachingService implements CachingServiceInterface
{
    /**
     * @throws CachingException
     */
    public function get(string $key): string
    {
        $value = apcu_fetch($key, $success);

        if (!$success) {
            throw new CachingException('Failed to get the value from the cache.');
        }

        return $value;
    }

    /**
     * @param ?int $ttl Time-to-live in seconds. Null means no expiry.
     *
     * @throws CachingException
     */
    public function set(string $key, string $value, ?int $ttl = null): void
    {
        $ttl ??= 0;

        if ($ttl < 0) {
            throw new CachingException('The ttl must be greater than or equal to 0.');
        }

        if (!apcu_store($key, $value, $ttl)) {
            throw new CachingException('Failed to set the value into the cache.');
        }
    }

    /**
     * @param \Closure(string): (string) $callback
     * @param ?int                       $ttl      Time-to-live in seconds. Null means no expiry.
     *
     * @throws CachingException
     */
    public function getOrSet(string $key, \Closure $callback, ?int $ttl = null): string
    {
        $ttl ??= 0;

        if ($ttl < 0) {
            throw new CachingException('The ttl must be greater than or equal to 0.');
        }

        try {
            return apcu_entry($key, $callback, $ttl);
        } catch (\Throwable $throwable) {
            throw new CachingException($throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * @throws CachingException
     */
    public function delete(string $key): void
    {
        if (!$this->has($key)) {
            return;
        }

        if (!apcu_delete($key)) {
            throw new CachingException('Failed to delete the value from the cache.');
        }
    }

    /**
     * @throws CachingException
     */
    public function clear(): void
    {
        apcu_clear_cache();
    }

    /**
     * @throws CachingException
     */
    public function has(string $key): bool
    {
        return apcu_exists($key);
    }
}
