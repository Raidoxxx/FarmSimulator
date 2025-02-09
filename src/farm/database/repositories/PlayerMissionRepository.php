<?php

declare(strict_types=1);

namespace farm\database\repositories;

use mysqli;
use RuntimeException;

class PlayerMissionRepository implements RepositoryInterface
{
    private mysqli $connection;
    private string $table = 'player_missions';

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Busca uma missão específica de um jogador.
     */
    public function findById(string $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $mission = $result->fetch_assoc();
        $stmt->close();

        return $mission ?: null;
    }

    /**
     * Busca uma missão específica de um jogador pelo UUID.
     */
    public function findByPlayerUuid(string $playerUuid): ?array{
        $sql = "SELECT * FROM {$this->table} WHERE player_uuid = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $playerUuid);
        $stmt->execute();
        $result = $stmt->get_result();
        $mission = $result->fetch_assoc();
        $stmt->close();

        return $mission ?: null;
    }

    /**
     * Retorna todas as missões em progresso dos jogadores.
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
     * Retorna todas as missões ativas de um jogador pelo UUID.
     */
    public function getMissionsByPlayer(string $playerId): array
    {
        $missions = [];
        $sql = "SELECT * FROM {$this->table} WHERE player_id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $playerId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $missions[] = $row;
        }

        $stmt->close();
        return $missions;
    }

    /**
     * Salva ou atualiza o progresso de uma missão do jogador.
     */
    public function save(array $data): void
    {
        //	id	player_uuid	mission_id	progress	completed	start_time
        $sql = " 
            INSERT INTO {$this->table} (player_uuid, mission_id, progress, completed, start_time) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                progress = ?,
                completed = ?,
                start_time = ?
                    
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("ssiiiiii",
            $data['player_uuid'],
            $data['mission_id'],
            $data['progress'],
            $data['completed'],
            $data['start_time'],
            $data['progress'],
            $data['completed'],
            $data['start_time']
        );

        if (!$stmt->execute()) {
            throw new RuntimeException("Failed to save mission progress: " . $stmt->error);
        }
    }

    /**
     * Marca uma missão como concluída para um jogador.
     */
    public function completeMission(string $playerId, string $missionId): void
    {
        $sql = "UPDATE {$this->table} 
                SET completed = 1 
                WHERE player_id = ? AND mission_id = ?";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("ss", $playerId, $missionId);

        if (!$stmt->execute()) {
            throw new RuntimeException("Failed to complete mission: " . $stmt->error);
        }

        $stmt->close();
    }

    /**
     * Remove uma missão do jogador (caso necessário resetar ou excluir).
     */
    public function delete(string $id): void
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("s", $id);

        if (!$stmt->execute()) {
            throw new RuntimeException("Failed to delete mission record: " . $stmt->error);
        }

        $stmt->close();
    }
}
