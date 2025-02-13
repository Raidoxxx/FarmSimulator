<?php

namespace farm\commands;

use farm\commands\subcommands\BaseSubCommand;
use farm\commands\subcommands\island\IslandSubCommand;
use farm\commands\subcommands\island\TeleportFarmSubCommand;
use farm\commands\subcommands\level\LevelSubCommand;
use farm\commands\subcommands\missions\ListMissionsSubCommand;
use farm\commands\subcommands\missions\MissionSubCommand;
use farm\player\FarmingPlayer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;


class FarmCommands extends Command
{
    private array $subcommands = [];

    public function __construct()
    {
        parent::__construct("farm", "Farm Simulator commands", "/farm <subcommand>", ["f"]);
        $this->setPermission(DefaultPermissions::ROOT_USER);
        $this->registerSubcommand(new MissionSubCommand());
        $this->registerSubcommand(new IslandSubCommand());
        $this->registerSubcommand(new LevelSubCommand());
    }

    public function registerSubcommand(BaseSubCommand $subcommand): void
    {
        $this->subcommands[] = $subcommand;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof Player) return;
        if(!$sender instanceof FarmingPlayer) return;

        if(isset($args[0])){
            $subcommand = $this->getSubcommand($args[0]);
            if($subcommand !== null){
                $subcommand->execute($sender, $commandLabel, $args);
            }else{
                $sender->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §cSubcomando não encontrado.");
            }
        }else{
            $this->sendHelp($sender);
        }
    }

    public function sendHelp(Player $player): void
    {
        $player->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §aComandos disponíveis:");
        foreach($this->subcommands as $subcommand){
            $player->sendMessage("- ".$subcommand->getName() . " §7" . $subcommand->getDescription());
        }
    }

    public function getSubcommands(): array
    {
        return $this->subcommands;
    }

    public function getSubcommand(string $name): ?BaseSubCommand
    {
        foreach($this->subcommands as $subcommand){
            if($subcommand->getName() === $name || in_array($name, $subcommand->getAliases(), true)){
                return $subcommand;
            }
        }
        return null;
    }
}