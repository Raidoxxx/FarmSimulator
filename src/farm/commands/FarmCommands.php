<?php

namespace farm\commands;

use farm\player\animations\camera\Camera;
use Jibix\Forms\form\type\MenuForm;
use Jibix\Forms\menu\Button;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\Server;

class FarmCommands extends Command
{
    use Camera;

    public function __construct()
    {
        parent::__construct("farm", "Farm Simulator commands", "/farm <subcommand>", ["f"]);
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof Player) return;

        if(!isset($args[0])) {
            $sender->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §aUse /farm <subcommand>");
            return;
        }

        switch($args[0]) {
            case "menu":
                $button = new Button("§8§lIr para Farm", function () use ($sender) {
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
                });

                $sender->sendForm(new MenuForm(
                    "§l§8Farm Simulator",
                    "", [$button]
                ));
                break;
            case "help":
                $sender->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §aComandos disponíveis:");
                $sender->sendMessage("§a/farm menu - Abre o menu do Farm Simulator");
                $sender->sendMessage("§a/farm help - Mostra os comandos disponíveis");
                break;
            default:
                $sender->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §aUse /farm help para ver os comandos disponíveis");
                break;
        }
    }


}