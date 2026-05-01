<?php

namespace Caching\Services;

use Caching\Exceptions\CachingException;

class JsonCachingService implements JsonCachingServiceInterface
{
    public function __construct(protected CachingServiceInterface $cachingService)
    {
    }

    /**
     * @return array<int|string,mixed>|string|float|int|bool|null
     *
     * @throws CachingException
     */
    public function get(string $key): array|string|float|int|bool|null
    {
        return $this->jsonToMixed($this->cachingService->get($key));
    }

    /**
     * @param array<int|string,mixed>|string|float|int|bool|null $value
     * @param ?int                                               $ttl   Time-to-live in seconds. Null means no expiry.
     *
     * @throws CachingException
     */
    public function set(string $key, array|string|float|int|bool|null $value, ?int $ttl = null): void
    {
        $this->cachingService->set($key, $this->mixedToJson($value), $ttl);
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
        $internalCallback = function (string $key) use ($callback) {
            return $this->mixedToJson($callback($key));
        };

        try {
            return $this->jsonToMixed($this->cachingService->getOrSet($key, $internalCallback, $ttl));
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
        $this->cachingService->delete($key);
    }

    /**
     * @throws CachingException
     */
    public function clear(): void
    {
        $this->cachingService->clear();
    }

    /**
     * @throws CachingException
     */
    public function has(string $key): bool
    {
        return $this->cachingService->has($key);
    }

    /**
     * @throws CachingException
     */
    private function mixedToJson(mixed $data): string
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
    private function jsonToMixed(string $json): mixed
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $throwable) {
            throw new CachingException($throwable->getMessage(), 0, $throwable);
        }
    }
}
