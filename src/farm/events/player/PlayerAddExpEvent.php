<?php

namespace farm\events\player;

use farm\events\FarmSimulatorEvent;
use pocketmine\player\Player;

class PlayerAddExpEvent extends FarmSimulatorEvent
{
    private int $exp;
    private Player $player;

    public function __construct(Player $player, int $exp)
    {
        $this->player = $player;
        $this->exp = $exp;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getExp(): int
    {
        return $this->exp;
    }
}