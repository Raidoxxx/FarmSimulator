<?php

namespace farm\database;

use mysqli;
use RuntimeException;

class MySQLDatabase implements DatabaseInterface
{
    private const DEFAULT_CHUNKS = '["16:16"]';
    private const DEFAULT_MONEY = 0;
    private const DEFAULT_LEVEL = 1;
    private const DEFAULT_EXP = 0;
    private const DEFAULT_EXP_TO_NEXT_LEVEL = 100;
    private const DEFAULT_FARM_SIZE = 0;

    private mysqli $connection;

    public function __construct(
        private string $host,
        private string $user,
        private string $password,
        private string $database,
        private string $table = 'players'
    ) {
        $this->connect();
        $this->initialize();
    }

    private function connect(): void
    {
        $this->connection = new mysqli(
            $this->host,
            $this->user,
            $this->password,
            $this->database
        );

        if ($this->connection->connect_error) {
            throw new RuntimeException("MySQL connection failed: " . $this->connection->connect_error);
        }

        // Definir charset para utf8mb4
        $this->connection->set_charset('utf8mb4');
    }

    public function initialize(): void
    {
        $query = "CREATE TABLE IF NOT EXISTS {$this->table} (
            uuid VARCHAR(36) PRIMARY KEY,
            chunks TEXT NOT NULL DEFAULT '" . self::DEFAULT_CHUNKS . "',
            money INT NOT NULL DEFAULT " . self::DEFAULT_MONEY . ",
            level INT NOT NULL DEFAULT " . self::DEFAULT_LEVEL . ",
            exp INT NOT NULL DEFAULT " . self::DEFAULT_EXP . ",
            expToNextLevel INT NOT NULL DEFAULT " . self::DEFAULT_EXP_TO_NEXT_LEVEL . ",
            farmSize INT NOT NULL DEFAULT " . self::DEFAULT_FARM_SIZE . "
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!$this->connection->query($query)) {
            throw new RuntimeException("Error creating table: " . $this->connection->error);
        }
    }

    private function getDefaultPlayerData(string $uuid): array
    {
        return [
            'uuid' => $uuid,
            'chunks' => self::DEFAULT_CHUNKS,
            'money' => self::DEFAULT_MONEY,
            'level' => self::DEFAULT_LEVEL,
            'exp' => self::DEFAULT_EXP,
            'expToNextLevel' => self::DEFAULT_EXP_TO_NEXT_LEVEL,
            'farmSize' => self::DEFAULT_FARM_SIZE
        ];
    }

    public function registerPlayer(string $uuid): void
    {
        try {
            $stmt = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (uuid, chunks, money, level, exp, expToNextLevel, farmSize) 
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                throw new RuntimeException("Prepare failed: " . $this->connection->error);
            }

            // Variáveis para bind_param
            $chunks = self::DEFAULT_CHUNKS;
            $money = self::DEFAULT_MONEY;
            $level = self::DEFAULT_LEVEL;
            $exp = self::DEFAULT_EXP;
            $expToNext = self::DEFAULT_EXP_TO_NEXT_LEVEL;
            $farmSize = self::DEFAULT_FARM_SIZE;

            $stmt->bind_param(
                "ssiiiii",
                $uuid,
                $chunks,
                $money,
                $level,
                $exp,
                $expToNext,
                $farmSize
            );

            if (!$stmt->execute()) {
                // Verifica se é erro de entrada duplicada
                if ($this->connection->errno === 1062) {
                    return; // Jogador já existe, não precisa registrar
                }
                throw new RuntimeException("Registration failed: " . $stmt->error);
            }

            $stmt->close();
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                return; // Duplicate entry, player já registrado
            }
            throw new RuntimeException("Database error: " . $e->getMessage());
        }
    }

    public function loadPlayerData(string $uuid): array
    {
        try {
            $stmt = $this->connection->prepare("SELECT * FROM {$this->table} WHERE uuid = ?");
            if (!$stmt) {
                throw new RuntimeException("Prepare failed: " . $this->connection->error);
            }

            $stmt->bind_param("s", $uuid);
            $stmt->execute();

            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            if ($data === null) {
                $this->registerPlayer($uuid);
                return $this->getDefaultPlayerData($uuid);
            }

            return $data;
        } catch (\mysqli_sql_exception $e) {
            throw new RuntimeException("Load data failed: " . $e->getMessage());
        }
    }

    public function savePlayerData(array $data): bool
    {
        $this->connection->begin_transaction();

        try {
            $stmt = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (uuid, chunks, money, level, exp, expToNextLevel, farmSize)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                chunks = VALUES(chunks),
                money = VALUES(money),
                level = VALUES(level),
                exp = VALUES(exp),
                expToNextLevel = VALUES(expToNextLevel),
                farmSize = VALUES(farmSize)"
            );

            if (!$stmt) {
                throw new RuntimeException("Prepare failed: " . $this->connection->error);
            }

            $stmt->bind_param(
                "ssiiiii",
                $data['uuid'],
                $data['chunks'],
                $data['money'],
                $data['level'],
                $data['exp'],
                $data['expToNextLevel'],
                $data['farmSize']
            );

            if (!$stmt->execute()) {
                throw new RuntimeException("Save failed: " . $stmt->error);
            }

            $this->connection->commit();
            return true;
        } catch (\mysqli_sql_exception $e) {
            $this->connection->rollback();
            throw new RuntimeException("Save data failed: " . $e->getMessage());
        } finally {
            $stmt->close();
        }
    }

    public function close(): void
    {
        try {
            if ($this->connection->ping()) {
                $this->connection->close();
            }
        } catch (\mysqli_sql_exception $e) {
            error_log("Error closing connection: " . $e->getMessage());
        }
    }
}