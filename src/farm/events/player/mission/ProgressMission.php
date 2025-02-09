<?php

namespace farm\events\player\mission;


use farm\events\FarmSimulatorEvent;
use farm\manager\missions\Mission;
use pocketmine\player\Player;

class ProgressMission extends FarmSimulatorEvent
{
    private string $player;
    private Mission $mission;
    private int $progress;

    public function __construct(Player $player, Mission $mission, int $progress)
    {
        $this->player = $player;
        $this->mission = $mission;
        $this->progress = $progress;
    }

    public function getPlayer(): string
    {
        return $this->player;
    }

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }
}