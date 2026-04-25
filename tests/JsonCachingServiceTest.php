<?php

namespace Tests;

use Caching\Exceptions\CachingException;
use Caching\Services\CachingServiceInterface;
use Caching\Services\JsonCachingService;
use Caching\Services\JsonCachingServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

class JsonCachingServiceTest extends TestCase
{
    protected MockObject&CachingServiceInterface $cachingService;

    protected JsonCachingServiceInterface $jsonCachingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cachingService = $this->getMockBuilder(CachingServiceInterface::class)->getMock();

        $this->jsonCachingService = new JsonCachingService($this->cachingService);
    }

    #[DataProvider('dataProviderGet')]
    public function testGet(mixed $expectedValue): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();

        $this->cachingService->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($this->mixedToJson($expectedValue));

        $this->assertEquals($expectedValue, $this->jsonCachingService->get($key));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function dataProviderGet(): array
    {
        return [
            [
                'expectedValue' => [
                    '1',
                ],
            ],
            [
                'expectedValue' => '1',
            ],
            [
                'expectedValue' => 1.0,
            ],
            [
                'expectedValue' => 1,
            ],
            [
                'expectedValue' => true,
            ],
            [
                'expectedValue' => null,
            ],
        ];
    }

    #[DataProvider('dataProviderSet')]
    public function testSet(mixed $expectedValue): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();
        $ttl = $this->fakerService->getDataTypeGenerator()->randomInteger(0, 60);

        $this->cachingService->expects($this->once())
            ->method('set')
            ->with($key, $this->mixedToJson($expectedValue), $ttl);

        $this->jsonCachingService->set($key, $expectedValue, $ttl);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function dataProviderSet(): array
    {
        return [
            [
                'expectedValue' => [
                    '1',
                ],
            ],
            [
                'expectedValue' => '1',
            ],
            [
                'expectedValue' => 1.0,
            ],
            [
                'expectedValue' => 1,
            ],
            [
                'expectedValue' => true,
            ],
            [
                'expectedValue' => null,
            ],
        ];
    }

    #[DataProvider('dataProviderGetOrSet')]
    public function testGetOrSet(mixed $expectedValue): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();
        $ttl = $this->fakerService->getDataTypeGenerator()->randomInteger(0, 60);

        $this->cachingService->expects($this->once())
            ->method('getOrSet')
            ->with($key, $this->isInstanceOf(\Closure::class), $ttl)
            ->willReturnCallback(function (string $key, \Closure $internalCallback, ?int $ttl = null) {
                return $internalCallback($key);
            });

        $this->assertEquals($expectedValue, $this->jsonCachingService->getOrSet(
            $key,
            function (string $key) use ($expectedValue) {
                return $expectedValue;
            },
            $ttl
        ));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function dataProviderGetOrSet(): array
    {
        return [
            [
                'expectedValue' => [
                    '1',
                ],
            ],
            [
                'expectedValue' => '1',
            ],
            [
                'expectedValue' => 1.0,
            ],
            [
                'expectedValue' => 1,
            ],
            [
                'expectedValue' => true,
            ],
            [
                'expectedValue' => null,
            ],
        ];
    }

    public function testGetOrSetCallbackThrowsException(): void
    {
        $this->expectException(CachingException::class);
        $this->expectExceptionMessage('nope.');

        $key = $this->fakerService->getDataTypeGenerator()->randomString();

        $this->cachingService->expects($this->once())
            ->method('getOrSet')
            ->with($key, $this->isInstanceOf(\Closure::class), null)
            ->willReturnCallback(function (string $key, \Closure $internalCallback, ?int $ttl = null) {
                return $internalCallback($key);
            });

        $this->jsonCachingService->getOrSet(
            $key,
            function (string $key) {
                throw new \Exception('nope.');
            }
        );
    }

    public function testDelete(): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();

        $this->cachingService->expects($this->once())
            ->method('delete')
            ->with($key);

        $this->jsonCachingService->delete($key);
    }

    public function testClear(): void
    {
        $this->cachingService->expects($this->once())
            ->method('clear');

        $this->jsonCachingService->clear();
    }

    public function testHas(): void
    {
        $key = $this->fakerService->getDataTypeGenerator()->randomString();

        $this->cachingService->expects($this->once())
            ->method('has')
            ->with($key)
            ->willReturn(true);

        $this->assertTrue($this->jsonCachingService->has($key));
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
