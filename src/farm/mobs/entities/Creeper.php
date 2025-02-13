<?php

namespace farm\mobs\entities;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Creeper extends Living
{
    public static function getNetworkTypeId(): string
    {
        return EntityIds::CREEPER;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.7, 0.6); //TODO: eye height ??
    }

    public function getName(): string
    {
        return "Creeper";
    }

    public function getDrops(): array
    {
        return [];
    }

    public function getXpDropAmount(): int
    {
        return 8;
    }
}