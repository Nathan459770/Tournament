<?php

namespace Nathan45\Tournament;

use Nathan45\Tournament\Commands\RankCommand;
use Nathan45\Tournament\Events\Event;
use Nathan45\Tournament\Events\EventManager;
use Nathan45\Tournament\Listeners\TournamentListener;
use Nathan45\Tournament\Utils\DataManager;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class Loader extends PluginBase
{
    private static self $instance;

    protected function onEnable(): void
    {
        self::$instance = $this;
        $this->getServer()->getPluginManager()->registerEvents(new TournamentListener(), $this);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () : void{foreach (EventManager::getInstance()->events as $event) if($event instanceof Event){ $event->update();}}), 20);
        $this->getServer()->getCommandMap()->register("rank", new RankCommand());
        DataManager::getInstance();
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    // Todo list: ranks, settings scoreboard, tester a plusieurs les events (à 2 ca fonctionne)
}