<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
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

    if (!REDIS_ENABLED || !class_exists('Redis')) {
        return null;
    }

    try {
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT, REDIS_TIMEOUT);
        if (REDIS_PASSWORD !== '') {
            $redis->auth(REDIS_PASSWORD);
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
