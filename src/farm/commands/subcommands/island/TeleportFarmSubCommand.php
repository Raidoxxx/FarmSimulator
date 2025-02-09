<?php

namespace farm\commands\subcommands\island;

use farm\commands\subcommands\BaseSubCommand;
use farm\player\animations\camera\Camera;
use farm\player\FarmingPlayer;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;

class TeleportFarmSubCommand extends BaseSubCommand
{
    use Camera;

    public function __construct()
    {
        parent::__construct(
            "tp",
            "Teleporta para a sua ilha.",
            "/farm island tp",
            ["teleport"]
        );
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof Player) return;
        if(!$sender instanceof FarmingPlayer) return;

        Server::getInstance()->getWorldManager()->loadWorld($sender->getName());
        if(Server::getInstance()->getWorldManager()->isWorldLoaded($sender->getName())){
            $world = Server::getInstance()->getWorldManager()->getWorldByName($sender->getName());
            if($world === null) {
                $sender->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §cVocê não possui um Farm!");
                return;
            }

            $sender->teleport($world->getSpawnLocation());
            $this->startIntro($sender, new Vector3(264, 75, 264));
        }else{
            $sender->sendMessage("§cOcorreu um erro!");
        }
    }
}