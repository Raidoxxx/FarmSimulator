<?php

namespace farm\mobs\entities;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Skeleton extends Living
{
    public static function getNetworkTypeId(): string
    {
        return EntityIds::SKELETON;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.9, 0.6); //TODO: eye height ??
    }

    public function getName(): string
    {
        return "Skeleton";
    }

    public function getDrops(): array
    {
        $drops = [
            VanillaItems::BONE()->setCount(mt_rand(1, 3)),
            VanillaItems::ARROW()->setCount(mt_rand(0, 2)),
            VanillaItems::BOW()->setDamage(VanillaItems::BOW()->getMaxDurability() - mt_rand(5, 25))
        ];

        return $drops;
    }

    public function getXpDropAmount(): int
    {
        return 5;
    }
}