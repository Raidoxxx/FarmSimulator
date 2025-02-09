<?php

namespace farm\commands\subcommands;

use pocketmine\command\CommandSender;

abstract class BaseSubCommand
{
    public function __construct(
        private string $name,
        private string $description,
        private string $usage,
        private array $aliases = []
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getUsage(): string
    {
        return $this->usage;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    abstract public function execute(CommandSender $sender, string $commandLabel, array $args): void;
}