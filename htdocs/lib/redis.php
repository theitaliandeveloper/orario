<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see https://www.gnu.org/licenses/.
*/

require_once __DIR__ . "/variables.php";

function redis_connection(): ?Redis
{
    static $redis = null;
    static $attempted = false;

    if ($attempted) {
        return $redis;
    }
    $attempted = true;

    if (!defined('REDIS_ENABLED') || !REDIS_ENABLED || !class_exists('Redis')) {
        return null;
    }

    try {
        $redis = new Redis();
        $host = defined('REDIS_HOST') ? REDIS_HOST : '127.0.0.1';
        $port = defined('REDIS_PORT') ? REDIS_PORT : 6379;
        $timeout = defined('REDIS_TIMEOUT') ? REDIS_TIMEOUT : 0.5;
        $password = defined('REDIS_PASSWORD') ? REDIS_PASSWORD : '';
        $redis->connect($host, $port, $timeout);
        if ($password !== '') {
            $redis->auth($password);
        }
        return $redis;
    } catch (Throwable $exception) {
        error_log('Redis non disponibile: ' . $exception->getMessage());
        $redis = null;
        return null;
    }
}

function redis_occupancy_version(): string
{
    $redis = redis_connection();
    if (!$redis) {
        return '0';
    }

    try {
        $version = $redis->get('homepage:occupancy:version');
        return $version === false ? '0' : (string)$version;
    } catch (Throwable $exception) {
        error_log('Lettura versione cache Redis fallita: ' . $exception->getMessage());
        return '0';
    }
}

function redis_invalidate_occupancy_cache(): void
{
    $redis = redis_connection();
    if (!$redis) {
        return;
    }

    try {
        $redis->incr('homepage:occupancy:version');
    } catch (Throwable $exception) {
        error_log('Invalidazione cache Redis fallita: ' . $exception->getMessage());
    }
}

function redis_get_cached(string $key): ?string
{
    $redis = redis_connection();
    if (!$redis) {
        return null;
    }

    try {
        $value = $redis->get($key);
        return $value === false ? null : (string)$value;
    } catch (Throwable $exception) {
        error_log('Lettura cache Redis fallita: ' . $exception->getMessage());
        return null;
    }
}

function redis_set_cached(string $key, string $value, int $ttl): void
{
    $redis = redis_connection();
    if (!$redis) {
        return;
    }

    try {
        $redis->setex($key, $ttl, $value);
    } catch (Throwable $exception) {
        error_log('Scrittura cache Redis fallita: ' . $exception->getMessage());
    }
}

function redis_timetable_cache_key(string $type, $identifier): string
{
    $normalizedType = strtolower($type);
    $normalizedType = match ($normalizedType) {
        'classe' => 'class',
        'docente' => 'teacher',
        'laboratorio' => 'room',
        default => $normalizedType
    };

    return 'timetable:v' . redis_occupancy_version()
        . ':' . $normalizedType . ':' . hash('sha256', (string)$identifier);
}
