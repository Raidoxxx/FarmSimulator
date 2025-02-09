<?php

namespace farm;

use CameraAPI\CameraHandler;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\lang\KnownTranslationFactory as l10n;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;

class Loader
{
    private array $listeners = [];
    private array $commands = [];
    public function __construct()
    {
        $this->loadListeners();
        $this->loadCommands();
        $this->loadHandlers();
    }

    public function loadHandlers(): void
    {
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register(Main::getInstance());
        }
        if(!CameraHandler::isRegistered())
        {
            CameraHandler::register(Main::getInstance());
        }
    }

    private function loadListeners() : void
    {
        $listeners = scandir(__DIR__ . "/listeners");
        foreach ($listeners as $listener) {
            if($listener === "." or $listener === "..") continue;
            $listener = str_replace(".php", "", $listener);
            $listener = "farm\\listeners\\" . $listener;
            $this->listeners[] = new $listener();
        }

        $this->registerListeners();
    }

    private function loadCommands() : void
    {
        $commands = scandir(__DIR__ . "/commands");
        foreach ($commands as $command) {
            if($command === "." or $command === "..") continue;
            $command = str_replace(".php", "", $command);
            if(is_dir(__DIR__ . "/commands/" . $command)) continue;
            $command = "farm\\commands\\" . $command;
            $this->commands[] = new $command();
        }

        $this->registerCommands();
    }

    public function getListeners() : array
    {
        return $this->listeners;
    }

    public function getCommands() : array
    {
        return $this->commands;
    }

    public function registerListeners(): void
    {
        foreach ($this->listeners as $listener) {
            Main::getInstance()->getServer()->getPluginManager()->registerEvents($listener, Main::getInstance());
        }
    }

    public function registerCommands(): void
    {
        foreach ($this->commands as $command) {
            Main::getInstance()->getServer()->getCommandMap()->register("farm", $command);
        }
    }

    public function registerPermission(string $permission, string $description, bool $default = false): Permission
    {
        $consoleRoot = DefaultPermissions::registerPermission(new Permission(DefaultPermissions::ROOT_CONSOLE, l10n::pocketmine_permission_group_console()));
        $operatorRoot = DefaultPermissions::registerPermission(new Permission(DefaultPermissions::ROOT_OPERATOR, l10n::pocketmine_permission_group_operator()), [$consoleRoot]);
        $everyoneRoot = DefaultPermissions::registerPermission(new Permission(DefaultPermissions::ROOT_USER, l10n::pocketmine_permission_group_user()), [$operatorRoot]);

        if($default) {
           return DefaultPermissions::registerPermission(new Permission($permission, $description), [$everyoneRoot]);
        }

        return DefaultPermissions::registerPermission(new Permission($permission, $description), [$operatorRoot]);
    }
}