<?php

declare(strict_types=1);

namespace Webclient\Extension\Cookie;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Webclient\Extension\Cookie\Cookie\Storage;

final class CookieClientDecorator implements ClientInterface
{
    private ClientInterface $client;
    private Storage $storage;

    public function __construct(ClientInterface $client, Storage $storage)
    {
        $this->client = $client;
        $this->storage = $storage;
    }

    /**
     * @inheritDoc
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();
        $cookies = $this->storage->get($uri);
        if ($cookies) {
            if ($request->hasHeader('Cookie')) {
                foreach (explode(',', $request->getHeaderLine('Cookie')) as $line) {
                    $line = trim($line);
                    list($name, $value) = array_replace([null, null], explode('=', $line, 2));
                    $name = trim($name);
                    $value = trim($value);
                    if (!$name || !$value) {
                        continue;
                    }
                    $cookies[$name] = $value;
                }
            }
            foreach ($cookies as $cookie => $value) {
                $request = $request->withAddedHeader('Cookie', $cookie . '=' . $value);
            }
        }
        $response = $this->client->sendRequest($request);
        if ($response->hasHeader('Set-Cookie')) {
            foreach ($response->getHeader('Set-Cookie') as $header) {
                $this->setCookieFromHeader($header, $uri->getHost());
            }
        }
        return $response;
    }

    public function __destruct()
    {
        $this->storage->save();
    }

    /**
     * @param string $header
     * @param string $domain
     */
    private function setCookieFromHeader(string $header, string $domain)
    {
        $arr = explode(';', $header);
        $cookies = [];
        $path = '/';
        $expires = 0;
        $secure = false;
        foreach ($arr as $item) {
            list($key, $value) = array_replace(['', ''], explode('=', $item, 2));
            $key = trim($key);
            $value = trim($value);
            switch (strtolower($key)) {
                case 'domain':
                    if ($value) {
                        $domain = $value;
                    }
                    break;
                case 'path':
                    $path = $value ?: '/';
                    break;
                case 'secure':
                    $secure = true;
                    break;
                case 'expires':
                    $timestamp = strtotime($value);
                    $expires = max($timestamp, 0);
                    break;
                case 'httponly':
                    break;
                default:
                    $cookies[$key] = $value;
            }
        }
        foreach ($cookies as $key => $value) {
            $this->storage->set($key, $value, $domain, $path, $expires, $secure);
        }
    }
}
