<?php

namespace Tests;

use Caching\Exceptions\CachingException;
use Caching\Services\ApcuCachingService;
use Caching\Services\CachingServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class ApcuCachingServiceTest extends TestCase
{
    protected CachingServiceInterface $cachingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cachingService = new ApcuCachingService();
    }

    public function testGet(): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();
        $expectedValue = $this->fakerService->getDataTypeGenerator()->randomString();
        $ttl = $this->fakerService->getDataTypeGenerator()->randomInteger(0, 60);

        $this->cachingService->set($key, $expectedValue, $ttl);

        $this->assertTrue($this->cachingService->has($key));
        $this->assertEquals($ttl, $this->getTtl($key));
        $this->assertEquals($expectedValue, $this->cachingService->get($key));
    }

    public function testSet(): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();
        $expectedValue = $this->fakerService->getDataTypeGenerator()->randomString();
        $ttl = $this->fakerService->getDataTypeGenerator()->randomInteger(0, 60);

        $this->cachingService->set($key, $expectedValue, $ttl);

        $this->assertTrue($this->cachingService->has($key));
        $this->assertEquals($ttl, $this->getTtl($key));
        $this->assertEquals($expectedValue, $this->cachingService->get($key));
    }

    #[DataProvider('dataProviderSetTtl')]
    public function testSetTtl(?int $ttl, int $expectedTtl): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();
        $expectedValue = $this->fakerService->getDataTypeGenerator()->randomString();

        $this->cachingService->set($key, $expectedValue, $ttl);

        $this->assertTrue($this->cachingService->has($key));
        $this->assertEquals($expectedTtl, $this->getTtl($key));
        $this->assertEquals($expectedValue, $this->cachingService->get($key));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function dataProviderSetTtl(): array
    {
        return [
            [
                'ttl' => null,
                'expectedTtl' => 0,
            ],
            [
                'ttl' => 1,
                'expectedTtl' => 1,
            ],
        ];
    }

    public function testSetInvalidTtl(): void
    {
        $this->expectException(CachingException::class);
        $this->expectExceptionMessage('The ttl must be greater than or equal to 0.');

        $this->cachingService->set(
            $this->fakerService->getDataTypeGenerator()->randomString(),
            $this->fakerService->getDataTypeGenerator()->randomString(),
            $this->fakerService->getDataTypeGenerator()->randomInteger(PHP_INT_MIN, -1)
        );
    }

    public function testGetOrSet(): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();
        $expectedValue = $this->fakerService->getDataTypeGenerator()->randomString();
        $ttl = $this->fakerService->getDataTypeGenerator()->randomInteger(0, 60);

        $value = $this->cachingService->getOrSet(
            $key,
            function (string $key) use ($expectedValue) {
                return $expectedValue;
            },
            $ttl
        );

        $this->assertTrue($this->cachingService->has($key));
        $this->assertEquals($ttl, $this->getTtl($key));
        $this->assertEquals($expectedValue, $value);
    }

    #[DataProvider('dataProviderGetOrSetTtl')]
    public function testGetOrSetTtl(?int $ttl, int $expectedTtl): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();
        $expectedValue = $this->fakerService->getDataTypeGenerator()->randomString();

        $value = $this->cachingService->getOrSet(
            $key,
            function (string $key) use ($expectedValue) {
                return $expectedValue;
            },
            $ttl
        );

        $this->assertTrue($this->cachingService->has($key));
        $this->assertEquals($expectedTtl, $this->getTtl($key));
        $this->assertEquals($expectedValue, $value);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function dataProviderGetOrSetTtl(): array
    {
        return [
            [
                'ttl' => null,
                'expectedTtl' => 0,
            ],
            [
                'ttl' => 1,
                'expectedTtl' => 1,
            ],
        ];
    }

    public function testGetOrSetInvalidTtl(): void
    {
        $this->expectException(CachingException::class);
        $this->expectExceptionMessage('The ttl must be greater than or equal to 0.');

        $this->cachingService->getOrSet(
            $this->fakerService->getDataTypeGenerator()->randomString(),
            function (string $key) {
                return $this->fakerService->getDataTypeGenerator()->randomString();
            },
            $this->fakerService->getDataTypeGenerator()->randomInteger(PHP_INT_MIN, -1)
        );
    }

    public function testGetOrSetCallbackThrowsException(): void
    {
        $this->expectException(CachingException::class);
        $this->expectExceptionMessage('nope.');

        $this->cachingService->getOrSet(
            $this->fakerService->getDataTypeGenerator()->randomString(),
            function (string $key) {
                throw new \Exception('nope.');
            }
        );
    }

    public function testDelete(): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();

        $this->cachingService->set($key, $this->fakerService->getDataTypeGenerator()->randomString());

        $this->cachingService->delete($key);

        $this->assertFalse($this->cachingService->has($key));
    }

    public function testDeleteKeyDoesNotExist(): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();

        $this->cachingService->delete($key);

        $this->assertFalse($this->cachingService->has($key));
    }

    public function testClear(): void
    {
        $key1 = $this->fakerService->getDataTypeGenerator()->randomString();
        $key2 = $this->fakerService->getDataTypeGenerator()->randomString();

        $this->cachingService->set($key1, $this->fakerService->getDataTypeGenerator()->randomString());
        $this->cachingService->set($key2, $this->fakerService->getDataTypeGenerator()->randomString());

        $this->cachingService->clear();

        $this->assertFalse($this->cachingService->has($key1));
        $this->assertFalse($this->cachingService->has($key2));
    }

    public function testHas(): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();

        $this->cachingService->set($key, $this->fakerService->getDataTypeGenerator()->randomString());

        $this->assertTrue($this->cachingService->has($key));
    }

    protected function getTtl(string $key): ?int
    {
        return apcu_key_info($key)['ttl'] ?? null;
    }
}
