<?php

namespace Nathan45\Tournament\Tasks;

use Nathan45\Tournament\Events\Event;
use pocketmine\scheduler\Task;

class StartEventDelayedTask extends Task
{
    public function __construct(private Event $event, private int $cooldown = -1)
    {
    }

    public function onRun(): void
    {
        $this->event->start($this->cooldown);
    }
}