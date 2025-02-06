<?php

namespace farm\events\player;

use farm\events\FarmSimulatorEvent;
use pocketmine\player\Player;

class PlayerLevelUp extends FarmSimulatorEvent
{
    private Player $player;
    private int $level;

    public function __construct(Player $player, int $level)
    {
        $this->player = $player;
        $this->level = $level;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getLevel(): int
    {
        return $this->level;
    }
}