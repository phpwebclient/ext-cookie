<?php

declare(strict_types=1);

namespace Stuff\Webclient\Extension\Cookie;

use Webclient\Extension\Cookie\Cookie\Storage as BaseStorage;

class Storage extends BaseStorage
{
    public function getItems(): array
    {
        $result = [];
        foreach ($this->all() as $scheme => $domains) {
            foreach ($domains as $domain => $cookies) {
                foreach ($cookies as $name => $data) {
                    $result[$name] = [
                        'value' => $data['value'],
                        'domain' => $domain,
                        'path' => $data['path'],
                        'httpOnly' => true,
                        'secure' => $scheme === 'https',
                        'expired' => $data['expired'],
                    ];
                }
            }
        }
        return $result;
    }

    public function save()
    {
    }
}
