<?php

namespace farm\commands\subcommands\missions;

use farm\commands\subcommands\BaseSubCommand;
use farm\Main;
use farm\player\FarmingPlayer;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class CompletedMissionsSubCommand extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            "completed",
            "Lista todas as missões completadas.",
            "/farm missions completed",
            ["c"]
        );
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof Player) return;
        if(!$sender instanceof FarmingPlayer) return;

        $missions = $sender->getMissions();
        $sender->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §aMissões completadas:");
        foreach($missions as $mission){
            $sender->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §a- " . $mission->getName());
        }
    }
}