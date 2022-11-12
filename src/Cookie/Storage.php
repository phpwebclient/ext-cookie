<?php

declare(strict_types=1);

namespace Webclient\Extension\Cookie\Cookie;

use Psr\Http\Message\UriInterface;

abstract class Storage
{
    /**
     * @var array<string, array<string, array<string, array{value: string, expired: int, path: string}>>>
     */
    private array $cookies = [];

    /**
     * @param UriInterface $uri
     * @return array<string, string>
     */
    final public function get(UriInterface $uri): array
    {
        $cookies = [];
        if (!array_key_exists($uri->getScheme(), $this->cookies)) {
            return [];
        }
        $time = time();
        foreach ($this->cookies[$uri->getScheme()] as $domain => $list) {
            $domain = mb_strtolower($domain);
            $len = mb_strlen($domain);
            $host = mb_strtolower($uri->getHost());
            $precision = mb_substr($domain, 0, 1) !== '.';
            $suffix = mb_substr(str_pad($host, $len, '.', STR_PAD_LEFT), -$len);
            if (($precision && $host !== $domain) || $suffix !== $domain) {
                continue;
            }
            foreach ($list as $name => $cookie) {
                if ($cookie['expired'] > 0 && $cookie['expired'] < $time) {
                    continue;
                }
                $path = '/' . ltrim(rtrim($uri->getPath(), '/'), '/') . '/';
                $cookiePath = '/' . ltrim(rtrim($cookie['path'], '/'), '/') . '/';
                if ($cookiePath === '//') {
                    $cookiePath = '/';
                }
                if (mb_strpos($path, $cookiePath) !== 0) {
                    continue;
                }
                $cookies[$name] = $cookie['value'];
            }
        }
        return $cookies;
    }

    final public function set(
        string $name,
        string $value,
        string $domain,
        string $path = '/',
        int $expire = 0,
        bool $secure = false
    ): void {
        $scheme = 'http' . ($secure ? 's' : '');
        $name = trim($name);
        if (!$name) {
            return;
        }
        $value = trim($value);
        if (!$value) {
            if (
                array_key_exists($scheme, $this->cookies)
                && array_key_exists($domain, $this->cookies[$scheme])
                && array_key_exists($name, $this->cookies[$scheme][$domain])
            ) {
                unset($this->cookies[$scheme][$domain][$name]);
                if (empty($this->cookies[$scheme][$domain])) {
                    unset($this->cookies[$scheme][$domain]);
                }
                if (empty($this->cookies[$scheme])) {
                    unset($this->cookies[$scheme]);
                }
                return;
            }
        }
        $this->cookies[$scheme][$domain][$name] = [
            'value' => $value,
            'expired' => max($expire, 0),
            'path' => '/' . ltrim($path, '/'),
        ];
    }

    abstract public function save(): void;

    /**
     * @return array<string, array<string, array<string, array{value: string, expired: int, path: string}>>>
     */
    protected function all(): array
    {
        return $this->cookies;
    }
}
