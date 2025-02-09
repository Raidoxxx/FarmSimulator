<?php

namespace farm\listeners;

use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\player\Player;

class ProtectionItem implements Listener
{
    public const DROP_PROTECTION = "dropProtectionDuration";
    public const KEY = "FarmSimulatorItemProtection";
    public function onDropItem(EntityItemPickupEvent  $event) : void
    {
        $player = $event->getEntity();
        $itemEntity = $event->getOrigin();
        if($player instanceof Player and $itemEntity instanceof ItemEntity) {
            $item = $itemEntity->getItem();
            $itemNbt = $item->getNamedTag();
            if(($itemOwner = $itemNbt->getCompoundTag(self::KEY)?->getString(self::DROP_PROTECTION)) !== null and $itemOwner !== $player->getName()) {
                $event->cancel();
            }
        }
    }
}