<?php

namespace Caching\Services;

use Caching\Exceptions\CachingException;

class ApcuCachingService implements CachingServiceInterface
{
    /**
     * @return array<int|string,mixed>|string|float|int|bool|null
     *
     * @throws CachingException
     */
    public function get(string $key): array|string|float|int|bool|null
    {
        $value = apcu_fetch($key, $success);

        if (!$success) {
            throw new CachingException('Failed to get the value from the cache.');
        }

        return $this->jsonToMixed($value);
    }

    /**
     * @param array<int|string,mixed>|string|float|int|bool|null $value
     * @param ?int                                               $ttl   Time-to-live in seconds. Null means no expiry.
     *
     * @throws CachingException
     */
    public function set(string $key, array|string|float|int|bool|null $value, ?int $ttl = null): void
    {
        $ttl ??= 0;

        if ($ttl < 0) {
            throw new CachingException('The ttl must be greater than or equal to 0.');
        }

        if (!apcu_store($key, $this->mixedToJson($value), $ttl)) {
            throw new CachingException('Failed to set the value into the cache.');
        }
    }

    /**
     * @param \Closure(string): (array<int|string,mixed>|string|float|int|bool|null) $callback
     * @param ?int                                                                   $ttl      Time-to-live in seconds. Null means no expiry.
     *
     * @return array<int|string,mixed>|string|float|int|bool|null
     *
     * @throws CachingException
     */
    public function getOrSet(string $key, \Closure $callback, ?int $ttl = null): array|string|float|int|bool|null
    {
        $ttl ??= 0;

        if ($ttl < 0) {
            throw new CachingException('The ttl must be greater than or equal to 0.');
        }

        $internalCallback = function (string $key) use ($callback) {
            return $this->mixedToJson($callback($key));
        };

        try {
            return $this->jsonToMixed(apcu_entry($key, $internalCallback, $ttl));
        } catch (CachingException $exception) {
            throw $exception;
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

    /**
     * @throws CachingException
     */
    protected function mixedToJson(mixed $data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR, 512);
        } catch (\Throwable $throwable) {
            throw new CachingException($throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * @throws CachingException
     */
    protected function jsonToMixed(string $json): mixed
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $throwable) {
            throw new CachingException($throwable->getMessage(), 0, $throwable);
        }
    }
}
