<?php

namespace farm\commands\subcommands\missions;

use farm\commands\subcommands\BaseSubCommand;
use farm\Main;
use farm\manager\missions\Mission;
use farm\player\FarmingPlayer;
use Jibix\Forms\form\type\MenuForm;
use Jibix\Forms\menu\Button;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class SelectMissionsSubCommand extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            "select",
            "Seleciona uma missão.",
            "/farm missions select",
            ["s"]
        );
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof Player) return;
        if(!$sender instanceof FarmingPlayer) return;

        $missions = Main::getInstance()->getPlayerManager()->getMissions();

        $buttons = [];

        /** @var Mission $mission */
        foreach($missions as $mission){
            $completed = $sender->hasMission($mission);
            $description = $completed ? "§aConcluída" : "§cNão concluída" . "§r\n" . $mission->getDescription();
            $buttons[] = new Button($mission->getName()."\n$description", function(Player $player) use ($mission){
                if($player instanceof FarmingPlayer && $player->hasMission($mission)){
                    $player->sendMessage(Main::$prefix." §cVocê já tem essa missão.");
                    return;
                }
                $player->sendMessage(Main::$prefix." §aMissão selecionada: " . $mission->getName());
                $player->sendMessage($mission->getDescription());
                $player->addMission($mission);
            });
        }

        $sender->sendForm(new MenuForm("Selecione uma missão", "Selecione uma missão para começar.", $buttons));
    }
}