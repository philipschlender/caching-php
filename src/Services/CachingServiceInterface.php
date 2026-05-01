<?php

namespace Caching\Services;

use Caching\Exceptions\CachingException;

interface CachingServiceInterface
{
    /**
     * @throws CachingException
     */
    public function get(string $key): string;

    /**
     * @param ?int $ttl Time-to-live in seconds. Null means no expiry.
     *
     * @throws CachingException
     */
    public function set(string $key, string $value, ?int $ttl = null): void;

    /**
     * @param \Closure(string): (string) $callback
     * @param ?int                       $ttl      Time-to-live in seconds. Null means no expiry.
     *
     * @throws CachingException
     */
    public function getOrSet(string $key, \Closure $callback, ?int $ttl = null): string;

    /**
     * @throws CachingException
     */
    public function delete(string $key): void;

    /**
     * @throws CachingException
     */
    public function clear(): void;

    /**
     * @throws CachingException
     */
    public function has(string $key): bool;
}
