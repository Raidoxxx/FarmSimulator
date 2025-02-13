<?php

namespace farm;

use farm\database\models\Database;
use farm\manager\PlayerManager;
use farm\world\FarmWorld;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;

class Main extends PluginBase implements Listener
{

    public static string $prefix = "§l§e[§r§aFarm§eSimulator§l§e]§r ";
    private static Main $instance;
    private Database $database;
    private Loader $loader;

    private PlayerManager $playerManager;

    public function onEnable() :void
    {
        self::$instance = $this;

        $this->database = new Database();

        GeneratorManager::getInstance()->addGenerator(
            FarmWorld::class,
            "farmworld",
            fn() => null, true
        );

        $this->loader = new Loader();
        $this->saveConfig();

        $this->getLogger()->info("§bFarm Simulator carregado!");
        $this->getFarmPrices();

        $this->playerManager = new PlayerManager($this);
    }

    public function getPlayerManager(): PlayerManager
    {
        return $this->playerManager;
    }

    public function getMysqlConfig()
    {
        return $this->getConfig()->get("mysql");
    }

    public function getLoader(): Loader
    {
        return $this->loader;
    }

    public function getFarms() : array
    {
        return $this->getConfig()->get("farms");
    }

    public function getFarmPrices() : array
    {
        $prices = [];
        foreach ($this->getFarms() as $farm => $data) {
            $prices[$farm] = $data["price"];
        }
        return $prices;
    }

    public function getFarmLevels() : array
    {
        $levels = [];
        foreach ($this->getFarms() as $farm => $data) {
            $levels[$farm] = $data["level"];
        }
        return $levels;
    }

    public function getFarmXp() : array
    {
        $xp = [];
        foreach ($this->getFarms() as $farm => $data) {
            $xp[$farm] = $data["xp"];
        }
        return $xp;
    }

    public static function getInstance() : Main
    {
        return self::$instance;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }
}