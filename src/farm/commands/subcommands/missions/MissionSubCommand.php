<?php

namespace farm\commands\subcommands\missions;

use farm\commands\subcommands\BaseSubCommand;
use farm\player\FarmingPlayer;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class MissionSubCommand extends BaseSubCommand
{

    private array $subcommands = [];

    public function __construct()
    {
        parent::__construct(
            "missions",
            "Gerencie suas missões.",
            "/farm missions <list|completed>",
            ["m"]
        );

        $this->registerSubcommand(new ListMissionsSubCommand());
        $this->registerSubcommand(new CompletedMissionsSubCommand());
        $this->registerSubcommand(new SelectMissionsSubCommand());
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof Player) return;
        if(!$sender instanceof FarmingPlayer) return;

        if(isset($args[1])){
            $subcommand = $this->getSubcommand($args[1]);
            if($subcommand !== null){
                $subcommand->execute($sender, $commandLabel, $args);
            }else{
                $sender->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §cSubcomando não encontrado.");
                $this->sendHelp($sender);
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

    public function registerSubcommand(BaseSubCommand $subcommand): void
    {
        $this->subcommands[] = $subcommand;
    }

    public function getSubcommand(string $name): ?BaseSubCommand
    {
        foreach($this->subcommands as $subcommand){
            if($subcommand->getName() === $name || in_array($name, $subcommand->getAliases())){
                return $subcommand;
            }
        }
        return null;
    }
}