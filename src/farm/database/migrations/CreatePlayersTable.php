<?php

namespace farm\database\migrations;

use mysqli;
use RuntimeException;

class CreatePlayersTable
{
    private mysqli $connection;
    private string $table = 'players';

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function up(): void
    {
        // players table
        $query = "CREATE TABLE IF NOT EXISTS players (
        uuid CHAR(36) NOT NULL PRIMARY KEY,
        chunks TEXT NOT NULL DEFAULT '[\"16:16\"]',
        money INT NOT NULL DEFAULT 0,
        level INT NOT NULL DEFAULT 1,
        exp INT NOT NULL DEFAULT 0,
        expToNextLevel INT NOT NULL DEFAULT 100,
        farmSize INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!$this->connection->query($query)) {
            throw new RuntimeException("Error creating table: " . $this->connection->error);
        }
    }

    public function down(): void
    {
        $query = "DROP TABLE IF EXISTS {$this->table}";
        if (!$this->connection->query($query)) {
            throw new RuntimeException("Error dropping table: " . $this->connection->error);
        }
    }
}
