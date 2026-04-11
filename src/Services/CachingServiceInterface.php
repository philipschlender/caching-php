<?php

namespace Caching\Services;

use Caching\Exceptions\CachingException;

interface CachingServiceInterface
{
    /**
     * @return array<int|string,mixed>|string|float|int|bool|null
     *
     * @throws CachingException
     */
    public function get(string $key): array|string|float|int|bool|null;

    /**
     * @param array<int|string,mixed>|string|float|int|bool|null $value
     * @param ?int                                               $ttl   Time-to-live in seconds. Null means no expiry.
     *
     * @throws CachingException
     */
    public function set(string $key, array|string|float|int|bool|null $value, ?int $ttl = null): void;

    /**
     * @param \Closure(string): (array<int|string,mixed>|string|float|int|bool|null) $callback
     * @param ?int                                                                   $ttl      Time-to-live in seconds. Null means no expiry.
     *
     * @return array<int|string,mixed>|string|float|int|bool|null
     *
     * @throws CachingException
     */
    public function getOrSet(string $key, \Closure $callback, ?int $ttl = null): array|string|float|int|bool|null;

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
