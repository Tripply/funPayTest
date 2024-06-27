<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        $params = [];
        foreach ($args as $arg) {
            if (is_array($arg)) {
                // Преобразование массива в список или ассоциативный массив
                $params[] = $this->processArray($arg);
            } elseif ($arg === null) {
                $params[] = 'NULL';
            } elseif ($arg === true) {
                $params[] = '1';
            } elseif ($arg === false) {
                $params[] = '0';
            } elseif (is_int($arg)) {
                $params[] = (int)$arg;
            } elseif (is_float($arg)) {
                $params[] = (float)$arg;
            } elseif (is_string($arg)) {
                $params[] = "'" . $this->mysqli->real_escape_string($arg) . "'";
            } else {
                throw new Exception('Unsupported parameter type.');
            }
        }

        return vsprintf(str_replace('?', '%s', $query), $params);
    }

    public function skip()
    {
        return 'SKIP';
    }

    private function processArray(array $array): string
    {
        $processed = [];
        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $processed[] = $this->processValue($value);
            } elseif (is_string($key)) {
                $processed[] = "`$key` = " . $this->processValue($value);
            } else {
                throw new Exception('Invalid array format.');
            }
        }
        return implode(', ', $processed);
    }

    private function processValue($value): string
    {
        if (is_array($value)) {
            return '(' . implode(', ', array_map(fn ($v) => $this->processValue($v), $value)) . ')';
        } elseif ($value === null) {
            return 'NULL';
        } elseif ($value === true) {
            return '1';
        } elseif ($value === false) {
            return '0';
        } elseif (is_int($value)) {
            return (string)$value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value); // Floats need specific formatting
        } elseif (is_string($value)) {
            return "'" . $this->mysqli->real_escape_string($value) . "'";
        } else {
            throw new Exception('Unsupported parameter type.');
        }
    }
}
