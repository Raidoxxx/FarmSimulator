<?php

declare(strict_types=1);

namespace farm\database\repositories;

use mysqli;
use RuntimeException;

class PlayerRepository implements RepositoryInterface
{
    private mysqli $connection;
    private string $table = 'players';

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Busca um jogador pelo UUID.
     */
    public function findById(string $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE uuid = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $uuid);
        $stmt->execute();
        $result = $stmt->get_result();
        $player = $result->fetch_assoc();
        $stmt->close();

        return $player ?: null;
    }

    /**
     * Retorna todos os jogadores.
     */
    public function findAll(): array
    {
        $players = [];
        $sql = "SELECT * FROM {$this->table}";
        $result = $this->connection->query($sql);

        while ($row = $result->fetch_assoc()) {
            $players[] = $row;
        }

        return $players;
    }

    /**
     * Salva ou atualiza um jogador.
     */
    public function save(array $data): void
    {
        /**
         * $query = "CREATE TABLE IF NOT EXISTS players (
         * uuid CHAR(36) NOT NULL PRIMARY KEY,
         * chunks TEXT NOT NULL DEFAULT '[\"16:16\"]',
         * money INT NOT NULL DEFAULT 0,
         * level INT NOT NULL DEFAULT 1,
         * exp INT NOT NULL DEFAULT 0,
         * expToNextLevel INT NOT NULL DEFAULT 100,
         * farmSize INT NOT NULL DEFAULT 0
         */
        $sql = "INSERT INTO {$this->table} 
                (uuid, chunks, money, level, exp, expToNextLevel, farmSize)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                chunks = VALUES(chunks),
                money = VALUES(money),
                level = VALUES(level),
                exp = VALUES(exp),
                expToNextLevel = VALUES(expToNextLevel),
                farmSize = VALUES(farmSize)";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param(
            "ssiiiii",
            $data['uuid'],
            $data['chunks'],
            $data['money'],
            $data['level'],
            $data['exp'],
            $data['expToNextLevel'],
            $data['farmSize']);

        if (!$stmt->execute()) {
            throw new RuntimeException("Failed to save player: " . $stmt->error);
        }

        $stmt->close();
    }

    /**
     * Busca um jogador pelo UUID do jogador.
     */

    public function findByPlayerUuid(string $playerUuid): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE uuid = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $playerUuid);
        $stmt->execute();
        $result = $stmt->get_result();
        $player = $result->fetch_assoc();
        $stmt->close();

        return $player ?: null;
    }

    /**
     * Remove um jogador do banco de dados.
     */
    public function delete(string $id): void
    {
        $sql = "DELETE FROM {$this->table} WHERE uuid = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $id);

        if (!$stmt->execute()) {
            throw new RuntimeException("Failed to delete player: " . $stmt->error);
        }

        $stmt->close();
    }
}
