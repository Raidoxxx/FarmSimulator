<?php

declare(strict_types=1);

namespace farm\database\migrations;

use mysqli;
use RuntimeException;

class CreateMissionsTable
{
    private mysqli $connection;
    private string $table = 'missions';

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function up(): void
    {
        // missions table
        $sql = "CREATE TABLE IF NOT EXISTS missions (
        mission_id CHAR(36) NOT NULL PRIMARY KEY,
        event VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        goal INT NOT NULL,
        criteria TEXT NOT NULL,
        reward_money INT NOT NULL,
        reward_exp INT NOT NULL,
        time_limit INT DEFAULT NULL,
        per_life TINYINT(1) NOT NULL DEFAULT 0,
        item_rewards TEXT DEFAULT NULL,
        permission_rewards TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!$this->connection->query($sql)) {
            throw new RuntimeException("Error creating table: " . $this->connection->error);
        }
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS {$this->table}";
        if (!$this->connection->query($sql)) {
            throw new RuntimeException("Error dropping table: " . $this->connection->error);
        }
    }
}
