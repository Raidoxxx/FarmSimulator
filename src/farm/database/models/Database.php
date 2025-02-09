<?php

declare(strict_types=1);

namespace farm\database\models;

use farm\database\migrations\CreateMissionsTable;
use farm\database\migrations\CreatePlayerMissionsTable;
use farm\database\models\MySQLDatabase;
use farm\database\migrations\CreatePlayersTable;
use farm\database\repositories\MissionRepository;
use farm\database\repositories\PlayerMissionRepository;
use farm\database\repositories\PlayerRepository;
use farm\database\repositories\RepositoryInterface;
use RuntimeException;

class Database
{
    private MySQLDatabase $db;
    private array $repositories = [];
    public function __construct()
    {
        $this->db = new MySQLDatabase('localhost', 'root', '', 'standoff');
        $this->migrate();

        $this->repositories = [
            'players' => new PlayerRepository($this->db->getConnection()),
            'missions' => new MissionRepository($this->db->getConnection()),
            'player_missions' => new PlayerMissionRepository($this->db->getConnection()),
        ];
    }

    /**
     * @param string $name
     * @return RepositoryInterface
     */
    public function getRepository(string $name) : RepositoryInterface
    {
        return $this->repositories[$name];
    }

    public function migrate(): void
    {
        echo "Running migrations...\n";

        $migrations = [
            new CreatePlayersTable($this->db->getConnection()),
            new CreateMissionsTable($this->db->getConnection()),
            new CreatePlayerMissionsTable($this->db->getConnection()),
        ];

        foreach ($migrations as $migration) {
            try {
                $migration->up();
                echo "Migration " . get_class($migration) . " executed successfully.\n";
            } catch (RuntimeException $e) {
                echo "Error executing migration: " . $e->getMessage() . "\n";
            }
        }
    }

    public function rollback(): void
    {
        echo "Rolling back migrations...\n";

        $migrations = [
            new CreatePlayersTable($this->db->getConnection()),
        ];

        foreach (array_reverse($migrations) as $migration) {
            try {
                $migration->down();
                echo "Rollback " . get_class($migration) . " executed successfully.\n";
            } catch (RuntimeException $e) {
                echo "Error executing rollback: " . $e->getMessage() . "\n";
            }
        }
    }
}
