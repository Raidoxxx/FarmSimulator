<?php

namespace farm\database\migrations;

use RuntimeException;

class CreatePlayerMissionsTable
{
    public function __construct(
        private \mysqli $connection
    ) {
    }

    public function up(): void
    {
        // player_missions table
        $sql = "CREATE TABLE IF NOT EXISTS player_missions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        player_uuid CHAR(36) NOT NULL,
        mission_id CHAR(36) NOT NULL,
        progress INT NOT NULL,
        completed TINYINT(1) NOT NULL,
        start_time INT,
        FOREIGN KEY (player_uuid) REFERENCES players(uuid),
        FOREIGN KEY (mission_id) REFERENCES missions(mission_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!$this->connection->query($sql)) {
            throw new RuntimeException('Error creating table: ' . $this->connection->error);
        }
    }

    public function down(): void
    {
        $this->connection->query("DROP TABLE IF EXISTS player_missions");
    }
}