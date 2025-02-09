<?php

namespace farm\listeners;

use farm\events\player\PlayerAddExpEvent;
use farm\events\player\PlayerLevelUp;
use farm\player\FarmingPlayer;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;

class PlayerListener implements Listener
{
    public function onCreatePlayer(PlayerCreationEvent $event): void
    {
        $event->setPlayerClass(FarmingPlayer::class);
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $player->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §aBem-vindo ao Farm Simulator!");
    }

    public function onUseItem(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if($player instanceof FarmingPlayer) {
           if(!$player->handleItem($item)){
               $event->cancel();
           }
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if($player instanceof FarmingPlayer) {
            if(!$player->handleItem($item)){
                $event->cancel();
            }
        }
    }

    public function onLevelUp(PlayerLevelUp $event): void
    {
        $player = $event->getPlayer();
        $level = $event->getLevel();
        $player->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §aParabéns! Você subiu para o nível {$level}!");
    }

    public function onExp(PlayerAddExpEvent $event): void
    {
        $player = $event->getPlayer();
        $exp = $event->getExp();
        $player->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §aVocê ganhou {$exp} de experiência!");
    }
}