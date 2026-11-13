<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class EnvironmentFile
{
    private function __construct(private readonly string $path)
    {
    }

    /**
     * @throws RuntimeException
     */
    public static function at(string $path, string $template): self
    {
        if (! is_file($path) && ! @copy($template, $path)) {
            throw new RuntimeException('Die Datei '.$path.' konnte nicht angelegt werden.');
        }

        return new self($path);
    }

    public function get(string $key): ?string
    {
        if (preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $this->read(), $match) !== 1) {
            return null;
        }

        return trim($match[1], " \t\"'");
    }

    /**
     * @param  array<string, string|int|bool|null>  $values
     */
    public function set(array $values): void
    {
        $contents = $this->read();

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->format($value);
            $pattern = '/^#?\s*'.preg_quote($key, '/').'=.*$/m';

            $contents = preg_match($pattern, $contents) === 1
                ? (string) preg_replace_callback($pattern, static fn (): string => $line, $contents, 1)
                : rtrim($contents)."\n".$line."\n";
        }

        $this->write($contents);
    }

    /**
     * @param  list<string>  $keys
     */
    public function comment(array $keys): void
    {
        $contents = $this->read();

        foreach ($keys as $key) {
            $contents = (string) preg_replace('/^'.preg_quote($key, '/').'=/m', '# '.$key.'=', $contents);
        }

        $this->write($contents);
    }

    private function format(string|int|bool|null $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        if ($value === '' || preg_match('/^[A-Za-z0-9_.:\/@\-]+$/', $value) === 1) {
            return $value;
        }

        return '"'.addcslashes($value, '"\\$').'"';
    }

    private function read(): string
    {
        return (string) file_get_contents($this->path);
    }

    private function write(string $contents): void
    {
        if (file_put_contents($this->path, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Die Datei '.$this->path.' ist nicht beschreibbar.');
        }
    }
}
