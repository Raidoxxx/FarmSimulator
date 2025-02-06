<?php

namespace farm\manager;

use farm\Main;
use farm\player\FarmingPlayer;
use JsonException;
use pocketmine\utils\Config;

class PlayerManager
{
    private Config $data;

    public function __construct()
    {
        $this->data = new Config(Main::getInstance()->getDataFolder() . "players.yml", Config::YAML);
    }

    /**
     * @throws JsonException
     */
    public function load(FarmingPlayer $player): void
    {
        $data = $this->data->get($player->getXuid());
        if($data === null) {
            $this->data->set($player->getXuid(), [
                "missions" => [],
            ]);
            $this->data->save();
            $data = $this->data->get($player->getXuid());
        }
    }
}