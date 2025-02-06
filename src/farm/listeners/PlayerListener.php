<?php

namespace farm\listeners;

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
}