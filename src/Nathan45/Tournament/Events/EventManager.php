<?php

namespace Nathan45\Tournament\Events;

use Nathan45\Tournament\Loader;
use Nathan45\Tournament\Tasks\StartEventDelayedTask;
use Nathan45\Tournament\TPlayer;
use Nathan45\Tournament\Utils\IConfig;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class EventManager implements IConfig
{
    use SingletonTrait;

    private Server $server;

    public array $events = [];

    public function __construct()
    {
        $this->server = Server::getInstance();
    }

    public function getEvents(int $status = -1): array{
        return $status === -1 ? $this->events : array_filter($this->events, function ($var) use ($status){
            return $var instanceof Event && $var->getStatus() === $status;
        });
    }

    public function getEventById(string $id): null|Event
    {
        foreach ($this->events as $event) if($event instanceof Event && $event->getUniqueId() === $id) return $event;
        return null;
    }

    public function createEvent(TPlayer $hoster): void{
        $this->server->getWorldManager()->loadWorld(self::EVENT_MAP_NAME);
        $event = new Event($hoster, $this->server->getWorldManager()->getWorldByName(self::EVENT_MAP_NAME));
        $this->events[] = $event;
        $this->server->broadcastMessage(self::PREFIX . "§c{$hoster->getName()} has started an event!");
        $event->start(30);
    }

    public function removeEvent(Event $event): void{
        unset($this->events[array_search($event, $this->events, true)]);
        foreach ($event->getAll() as $p){
            if($p instanceof TPlayer) $p->spawn(TPlayer::SPAWN_LOBBY);
        }
    }

}