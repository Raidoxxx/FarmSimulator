<?php

namespace farm\database;

interface DatabaseInterface
{
    public function initialize(): void;

    public function loadPlayerData(string $uuid): array;

    public function savePlayerData(array $data): bool;

    public function close(): void;
}