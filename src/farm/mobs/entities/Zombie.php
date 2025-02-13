<?php

namespace farm\mobs\entities;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Zombie extends Living{

    public static function getNetworkTypeId() : string{ return EntityIds::ZOMBIE; }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(1.8, 0.6); //TODO: eye height ??
    }

    public function getName() : string{
        return "Zombie";
    }

    public function getDrops() : array{
        $drops = [
            VanillaItems::ROTTEN_FLESH()->setCount(mt_rand(0, 2))
        ];

        if(mt_rand(0, 199) < 5){
            switch(mt_rand(0, 2)){
                case 0:
                    $drops[] = VanillaItems::IRON_INGOT();
                    break;
                case 1:
                    $drops[] = VanillaItems::CARROT();
                    break;
                case 2:
                    $drops[] = VanillaItems::POTATO();
                    break;
            }
        }

        return $drops;
    }

    public function getXpDropAmount() : int{
        return 5;
    }

    public function getPickedItem() : ?Item{
        return VanillaItems::ZOMBIE_SPAWN_EGG();
    }
}