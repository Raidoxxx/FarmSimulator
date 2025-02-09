<?php

namespace farm\database\models;

use mysqli;
use RuntimeException;

class MySQLDatabase implements DatabaseInterface
{
    private mysqli $connection;

    public function __construct(
        private string $host,
        private string $user,
        private string $password,
        private string $database
    ) {
        $this->connect();
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

        $this->connection->set_charset('utf8mb4');
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
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
