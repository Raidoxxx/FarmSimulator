<?php

namespace farm;

use CameraAPI\CameraHandler;
use farm\database\MySQLDatabase;
use farm\listeners\PlayerListener;
use farm\player\FarmWorld;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;

class Main extends PluginBase implements Listener
{
    private static Main $instance;
    private static string $prefix = "§l§e[§r§aFarm§eSimulator§l§e]§r ";
    private MySQLDatabase $database;

    public function onEnable() :void
    {
        self::$instance = $this;
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
        if(!CameraHandler::isRegistered())
        {
            CameraHandler::register($this);
        }
        $this->database = new MySQLDatabase('localhost', 'root', '', 'standoff');

        GeneratorManager::getInstance()->addGenerator(
            FarmWorld::class,
            "farmworld",
            fn() => null, true
        );

        $this->getServer()->getCommandMap()->register("farm", new commands\FarmCommands());
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerListener(), $this);
        $this->saveConfig();

        $this->getLogger()->info("§bFarm Simulator carregado!");
        $this->getFarmPrices();
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

    public function getDatabase(): MySQLDatabase
    {
        return $this->database;
    }
}