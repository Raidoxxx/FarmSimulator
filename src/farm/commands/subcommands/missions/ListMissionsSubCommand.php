<?php

namespace farm\commands\subcommands\missions;

use farm\commands\subcommands\BaseSubCommand;
use farm\Main;
use farm\player\FarmingPlayer;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ListMissionsSubCommand extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            "list",
            "Lista todas as missões disponíveis.",
            "/farm missions list",
            ["l"]
        );
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof Player) return;
        if(!$sender instanceof FarmingPlayer) return;

        $missions = Main::getInstance()->getPlayerManager()->getMissions();
        $sender->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §aMissões disponíveis:");
        foreach($missions as $mission){
            $sender->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §a- " . $mission->getName());
        }
    }
}