<?php

declare(strict_types=1);

namespace farm\database\repositories;

interface RepositoryInterface
{
    /**
     * Busca um registro pelo ID.
     */
    public function findById(string $id): ?array;

    /**
     * Busca um registro pelo UUID do jogador.
     */
    public function findByPlayerUuid(string $playerUuid, callable $callback): void;

    /**
     * Retorna todos os registros da tabela.
     */
    public function findAll(): array;

    /**
     * Salva um novo registro ou atualiza um existente.
     */
    public function save(array $data): void;

    /**
     * Remove um registro pelo ID.
     */
    public function delete(string $id): void;
}
