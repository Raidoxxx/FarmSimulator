<?php

namespace farm\commands\subcommands\level;

use farm\commands\subcommands\BaseSubCommand;
use farm\Main;
use farm\player\FarmingPlayer;
use pocketmine\command\CommandSender;

class LevelSubCommand extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            "level",
            "Check your level",
            "/farm level",
            ["lvl"]
        );
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof FarmingPlayer) return;

        $level = $sender->getLevel();
        $sender->sendMessage(Main::$prefix." §aSeu level é: §f".$level);
    }
}