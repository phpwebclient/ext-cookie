<?php

declare(strict_types=1);

namespace Webclient\Extension\Cookie\Cookie;

use InvalidArgumentException;
use Throwable;

use function array_replace;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function implode;
use function ltrim;
use function mb_strtoupper;
use function mb_substr;
use function mkdir;
use function preg_split;
use function str_getcsv;
use function touch;
use function trim;

final class NetscapeCookieFile extends Storage
{
    private string $file;

    public function __construct(string $file)
    {
        $this->file = $file;
        if (!file_exists($this->file)) {
            $dir = dirname($this->file);
            if (!is_dir($dir)) {
                try {
                    mkdir($dir, 0755, true);
                } catch (Throwable $exception) {
                    throw new InvalidArgumentException('Can not create file ' . $file, 1, $exception);
                }
            }
            try {
                touch($this->file);
            } catch (Throwable $exception) {
                throw new InvalidArgumentException('Can not create file ' . $file, 1, $exception);
            }
        }
        try {
            $contents = file_get_contents($file);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Can not read file ' . $file, 1, $exception);
        }
        $lines = preg_split('/\r(\n)?/ui', $contents);
        foreach ($lines as $num => $line) {
            $line = trim($line);
            if (!$line || mb_substr($line, 0, 1) === '#') {
                continue;
            }
            $row = array_replace([null, 'FALSE', '/', 'FALSE', 0, null, null], str_getcsv($line, "\t"));
            $name = trim((string)$row[5]);
            $value = trim((string)$row[6]);
            if (!$name || !$value) {
                continue;
            }
            $domain = trim((string)$row[0]);
            $domain = ltrim($domain, '.');
            if (mb_strtoupper(trim((string)$row[1])) === 'TRUE') {
                $domain = '.' . $domain;
            }
            $secure = mb_strtoupper(trim((string)$row[3])) === 'TRUE';
            $path = trim((string)$row[2]);
            $expired = (int)$row[4];
            if ($expired < 0) {
                $expired = 0;
            }
            $this->set($name, $value, $domain, $path, $expired, $secure);
        }
    }

    public function save()
    {
        $contents = '';
        foreach ($this->all() as $scheme => $domains) {
            foreach ($domains as $domain => $cookies) {
                foreach ($cookies as $name => $data) {
                    if (!$data['expired']) {
                        continue;
                    }
                    $host = $domain;
                    $sub = 'FALSE';
                    if (mb_substr($domain, 0, 1) === '.') {
                        $host = mb_substr($domain, 1);
                        $sub = 'TRUE';
                    }
                    $line = implode("\t", [
                        $host,
                        $sub,
                        $data['path'],
                        $scheme === 'https' ? 'TRUE' : 'FALSE',
                        $data['expired'],
                        $name,
                        $data['value'],
                    ]);
                    $contents .= $line . "\r\n";
                }
            }
        }
        file_put_contents($this->file, $contents);
    }

    public function __destruct()
    {
        $this->save();
    }
}
