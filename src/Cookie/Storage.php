<?php

declare(strict_types=1);

namespace Webclient\Extension\Cookie\Cookie;

use Psr\Http\Message\UriInterface;

use function array_key_exists;
use function ltrim;
use function mb_strlen;
use function mb_strpos;
use function mb_strtolower;
use function mb_substr;
use function rtrim;
use function str_pad;
use function time;
use function trim;

abstract class Storage
{

    /**
     * @var array
     */
    private $cookies = [];

    /**
     * @inheritDoc
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

    /**
     * @inheritDoc
     */
    final public function set(
        string $name,
        string $value,
        string $domain,
        string $path = '/',
        $expire = 0,
        $secure = false
    ) {
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
            'expired' => $expire > 0 ? $expire : 0,
            'path' => '/' . ltrim($path, '/'),
        ];
    }

    abstract public function save();

    protected function all(): array
    {
        return $this->cookies;
    }
}
