<?php

namespace farm\events\player\mission;

use farm\events\FarmSimulatorEvent;
use farm\manager\missions\Mission;
use pocketmine\player\Player;

class CompleteMission extends FarmSimulatorEvent
{
    private Player $player;
    private Mission $mission;

    public function __construct(Player $player, Mission $mission)
    {
        $this->player = $player;
        $this->mission = $mission;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getMission(): Mission
    {
        return $this->mission;
    }
}