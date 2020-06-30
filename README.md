[![Latest Stable Version](https://img.shields.io/packagist/v/webclient/ext-cookie.svg?style=flat-square)](https://packagist.org/packages/webclient/ext-cookie)
[![Total Downloads](https://img.shields.io/packagist/dt/webclient/ext-cookie.svg?style=flat-square)](https://packagist.org/packages/webclient/ext-cookie/stats)
[![License](https://img.shields.io/packagist/l/webclient/ext-cookie.svg?style=flat-square)](https://github.com/phpwebclient/ext-cookie/blob/master/LICENSE)
[![PHP](https://img.shields.io/packagist/php-v/webclient/ext-cookie.svg?style=flat-square)](https://php.net)

# webclient/ext-cookie

Cookie extension for PSR-18 HTTP client. 

# Install

Install this package and your favorite [psr-18 implementation](https://packagist.org/providers/psr/http-client-implementation).

```bash
composer require webclient/ext-cookie:^1.0
```

# Using

```php
<?php

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Webclient\Extension\Cookie\Client;
use Webclient\Extension\Cookie\Cookie\Storage;

/** 
 * @var ClientInterface $client Your PSR-18 HTTP Client
 * @var Storage $storage Cookies storage. You may extends this class for implements your storage
 */
$http = new Client($client, $storage);

/** @var RequestInterface $request */
$response = $http->sendRequest($request);
```

# Provided cookies stores

* `\Webclient\Extension\Cookie\Cookie\ArrayStorage` - Cookies stored in memory.  
* `\Webclient\Extension\Cookie\Cookie\NetscapeCookieFile` - Cookies stored in a file ([format][https://curl.haxx.se/docs/http-cookies.html]).  