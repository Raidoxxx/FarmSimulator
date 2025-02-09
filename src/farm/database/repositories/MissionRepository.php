<?php

declare(strict_types=1);

namespace farm\database\repositories;

use farm\manager\missions\Mission;
use mysqli;
use RuntimeException;

class MissionRepository implements RepositoryInterface
{
    private mysqli $connection;
    private string $table = 'missions';

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Busca uma missão pelo ID.
     */
    public function findById(string $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE mission_id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $mission = $result->fetch_assoc();
        $stmt->close();

        return $mission ?: null;
    }

    /**
     * Retorna todas as missões.
     */
    public function findAll(): array
    {
        $missions = [];
        $sql = "SELECT * FROM {$this->table}";
        $result = $this->connection->query($sql);

        while ($row = $result->fetch_assoc()) {
            $missions[] = $row;
        }

        return $missions;
    }

    /**
     * Salva ou atualiza uma missão.
     */
    public function save(array $data): void
    {
        $sql = "
        INSERT INTO {$this->table} (mission_id, event, name, description, goal, criteria, reward_money, reward_exp, time_limit, per_life, item_rewards, permission_rewards) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            event = ?,
            name = ?,
            description = ?,
            goal = ?,
            criteria = ?,
            reward_money = ?,
            reward_exp = ?,
            time_limit = ?,
            per_life = ?,
            item_rewards = ?,
            permission_rewards = ?
    ";

        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare statement: " . $this->connection->error);
        }

        $stmt->bind_param(
            "ssssiiiiiisssssiiiiiiss", // Total de 23 caracteres
            // Parâmetros para o INSERT:
            $data['mission_id'],
            $data['event'],
            $data['name'],
            $data['description'],
            $data['goal'],
            $data['criteria'],
            $data['reward_money'],
            $data['reward_exp'],
            $data['time_limit'],
            $data['per_life'],
            $data['item_rewards'],
            $data['permission_rewards'],

            // Parâmetros para o UPDATE (na ordem definida na query):
            $data['event'],
            $data['name'],
            $data['description'],
            $data['goal'],
            $data['criteria'],
            $data['reward_money'],
            $data['reward_exp'],
            $data['time_limit'],
            $data['per_life'],
            $data['item_rewards'],
            $data['permission_rewards']
        );

        if (!$stmt->execute()) {
            throw new RuntimeException("Failed to save mission: " . $stmt->error);
        }

        $stmt->close();
    }

    public function findByPlayerUuid(string $playerUuid): ?array
    {
        return null;
    }

    /**
     * Remove uma missão do banco de dados.
     */
    public function delete(string $id): void
    {
        $sql = "DELETE FROM {$this->table} WHERE mission_id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $id);

        if (!$stmt->execute()) {
            throw new RuntimeException("Failed to delete mission: " . $stmt->error);
        }

        $stmt->close();
    }
}
