<?php

namespace farm\listeners;

use farm\player\FarmingPlayer;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;

class MissionListener implements Listener
{
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        if (!$player instanceof FarmingPlayer) return;

        foreach ($player->getMissions() as $mission) {
            if (
                !$mission->isCompleted() &&
                $mission->getEventClass() === BlockBreakEvent::class
            ) {
                $criteria = $mission->getCriteria();
                $blockType = $event->getBlock()->getTypeId();

                if ($blockType === $criteria['block_id']) {
                    $mission->increaseProgress(1, $player);
                }
            }
        }
    }
}