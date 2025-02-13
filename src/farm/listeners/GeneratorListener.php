<?php

namespace farm\listeners;

use pocketmine\block\Lava;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Water;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;
use pocketmine\math\Facing;

class GeneratorListener implements Listener
{
    public function onUpdateBlock(BlockUpdateEvent $event): void
    {
        $block = $event->getBlock();
        $water = false;
        $fence = false;

        $ores = [
            VanillaBlocks::IRON_ORE(),
            VanillaBlocks::GOLD_ORE(),
            VanillaBlocks::EMERALD_ORE(),
            VanillaBlocks::COAL_ORE(),
            VanillaBlocks::REDSTONE_ORE(),
            VanillaBlocks::DIAMOND_ORE(),
            VanillaBlocks::NETHER_QUARTZ_ORE()
        ];

        foreach (Facing::ALL as $side) {
            $nearBlock = $block->getSide($side);
            if ($nearBlock instanceof Water) {
                $water = true;
            } elseif ($nearBlock instanceof Lava) {
                $fence = true;
            }
            if ($water && $fence) {
                break;
            }
        }

        if ($water && $fence && $block->getTypeId() === VanillaBlocks::AIR()->getTypeId()) {
            $default = VanillaBlocks::COBBLESTONE();

            if(mt_rand(0, 100) > 95) {
                $default = $ores[array_rand($ores)];
            }

            $block->getPosition()->getWorld()->setBlock($block->getPosition(), $default, false);
        }
    }
}