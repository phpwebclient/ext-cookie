<?php

declare(strict_types=1);

namespace Stuff\Webclient\Extension\Cookie;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Handler implements RequestHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $headers = [
            'Content-Type' => 'application/json',
        ];
        $response = new Response(200, $headers, json_encode($request->getCookieParams()), '1.1');
        if (!array_key_exists('cookie', $query) || !is_array($query['cookie'])) {
            return $response;
        }
        $cookies = [];
        $host = $request->getUri()->getHost();
        $expired = date(DATE_RFC7231, time() + 60 * 60 * 24 * 7);
        $path = $request->getUri()->getPath();
        if (!$path) {
            $path = '/';
        }

        /** @var array<string, string> $queryCookies */
        $queryCookies = $query['cookie'];
        foreach ($queryCookies as $name => $item) {
            $cookies[$name]['value'] = $name . '=' . $item;
            $cookies[$name]['domain'] = 'Domain=' . $host;
            $cookies[$name]['path'] = 'Path=' . $path;
            $cookies[$name]['expired'] = 'Expires=' . $expired;
            $cookies[$name]['httponly'] = 'HttpOnly';
            if ($request->getUri()->getScheme() === 'https') {
                $cookies[$name]['secure'] = 'Secure';
            }
        }

        /** @var string[] $querySubdomain */
        $querySubdomain = (array)($query['subdomain'] ?? []);
        foreach ($querySubdomain as $name) {
            if (!array_key_exists($name, $cookies)) {
                continue;
            }
            $cookies[$name]['domain'] = 'Domain=.' . $host;
        }

        /** @var string[] $queryTemp */
        $queryTemp = (array)($query['temp'] ?? []);
        foreach ($queryTemp as $name) {
            if (!array_key_exists($name, $cookies)) {
                continue;
            }
            unset($cookies[$name]['expired']);
        }

        foreach ($cookies as $cookie) {
            $response = $response->withAddedHeader('Set-Cookie', implode('; ', $cookie));
        }
        return $response;
    }
}
