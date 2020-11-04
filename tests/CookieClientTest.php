<?php

declare(strict_types=1);

namespace Tests\Webclient\Extension\Cookie;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Stuff\Webclient\Extension\Cookie\Handler;
use Stuff\Webclient\Extension\Cookie\Storage;
use Webclient\Extension\Cookie\Client;
use Webclient\Fake\Client as FakeClient;

class CookieClientTest extends TestCase
{

    /**
     * @param bool $secure
     * @param string $host
     * @param string $path
     *
     * @dataProvider provideSetToStorage
     *
     * @throws ClientExceptionInterface
     */
    public function testSetToStorage(bool $secure, string $host, string $path)
    {

        $storage = new Storage();

        $client = new Client(new FakeClient(new Handler()), $storage);

        $set = [
            'cookie' => [
                'foo' => 'baz',
                'bar' => 'beer',
                'sid' => 'session'
            ],
            'subdomain' => ['sid', 'bar'],
            'temp' => ['foo', 'bar'],
        ];
        $uri = 'http' . ($secure ? 's' : '') . '://' . $host . $path . '?' . http_build_query($set);
        $request = new Request('GET', $uri, ['Accept' => 'text/plain']);
        $client->sendRequest($request);

        $path = $path ? $path : '/';
        $cookies = $storage->getItems();
        foreach ($set['cookie'] as $name => $value) {
            $this->assertArrayHasKey($name, $cookies);
            $domain = in_array($name, $set['subdomain']) ? '.' . $host : $host;
            $temp = in_array($name, $set['temp']);
            $this->assertStoredCookie($cookies[$name], $value, $domain, $path, true, $secure, $temp);
        }
    }

    /**
     * @param string $uri
     * @param array $expected
     *
     * @dataProvider provideGetFromStorage
     *
     * @throws ClientExceptionInterface
     */
    public function testGetFromStorage(string $uri, array $expected)
    {
        $set = [
            [
                'name' => 'c1',
                'value' => 'v1',
                'domain' => '.localhost',
                'path' => '/',
                'expired' => 0,
                'secure' => true,
            ],
            [
                'name' => 'c2',
                'value' => 'v2',
                'domain' => '.phpunit',
                'path' => '/path/to',
                'expired' => time() + 86400,
                'secure' => true,
            ],
            [
                'name' => 'c3',
                'value' => 'v3',
                'domain' => '.phpunit',
                'path' => '/',
                'expired' => time() + 86400,
                'secure' => true,
            ],
            [
                'name' => 'c4',
                'value' => 'v4',
                'domain' => 'phpunit',
                'path' => '/',
                'expired' => time() + 86400,
                'secure' => true,
            ],
            [
                'name' => 'c5',
                'value' => 'v5',
                'domain' => '.phpunit',
                'path' => '/',
                'expired' => time() - 86400,
                'secure' => true,
            ],
            [
                'name' => 'c6',
                'value' => 'v6',
                'domain' => 'localhost',
                'path' => '/',
                'expired' => time() - 86400,
                'secure' => true,
            ],
            [
                'name' => 'c7',
                'value' => 'v7',
                'domain' => 'localhost',
                'path' => '/',
                'expired' => 0,
                'secure' => false,
            ],
        ];

        $storage = new Storage();
        foreach ($set as $arr) {
            $storage->set($arr['name'], $arr['value'], $arr['domain'], $arr['path'], $arr['expired'], $arr['secure']);
        }


        $client = new Client(new FakeClient(new Handler()), $storage);
        $request = new Request('GET', $uri, ['Accept' => 'text/plain']);
        $response = $client->sendRequest($request);
        $json = $response->getBody()->__toString();
        $cookies = json_decode($json, true);
        $this->assertCount(count($expected), $cookies);
        foreach ($expected as $name => $value) {
            $this->assertArrayHasKey($name, $cookies);
            $this->assertSame($value, $cookies[$name]);
        }
    }

    public function provideSetToStorage(): array
    {
        return [
            [true, 'localhost', ''],
            [false, 'packagist.org', '/packages'],
        ];
    }

    public function provideGetFromStorage(): array
    {
        return [
            ['https://localhost/path/to/url', ['c1' => 'v1']],
            ['https://phpunit/path/to/url', ['c2' => 'v2', 'c3' => 'v3', 'c4' => 'v4']],
            ['https://phpunit/path/to', ['c2' => 'v2', 'c3' => 'v3', 'c4' => 'v4']],
            ['https://phpunit/path/tourl', ['c3' => 'v3', 'c4' => 'v4']],
            ['https://sub.phpunit/path/tourl', ['c3' => 'v3']],
            ['http://localhost/', ['c7' => 'v7']],
        ];
    }

    private function assertStoredCookie(
        array $cookie,
        string $value,
        string $domain,
        string $path,
        bool $httpOnly,
        bool $secure,
        bool $temporary
    ) {
        $this->assertSame($value, $cookie['value']);
        $this->assertSame($domain, $cookie['domain']);
        $this->assertSame($path, $cookie['path']);
        $this->assertSame($httpOnly, $cookie['httpOnly']);
        $this->assertSame($secure, $cookie['secure']);
        $this->assertSame($temporary, $cookie['expired'] === 0);
    }
}
