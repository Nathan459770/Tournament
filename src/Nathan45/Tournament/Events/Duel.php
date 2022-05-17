<?php

namespace Nathan45\Tournament\Events;

use Nathan45\Tournament\TPlayer;
use Nathan45\Tournament\Utils\IConfig;
use pocketmine\data\bedrock\EffectIds;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\Limits;

class Duel implements IConfig
{
    const STATUS_STARTING = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_ENDING = 2;

    const COOLDOWN_BEFORE_START = 5;

    private int $status = 0;

    private int $cooldown;

    public function __construct(private ?TPlayer $p1, private ?TPlayer $p2, private Event $event, )
    {
        if($this->p1 === null || $this->p2 === null) {
            $this->event->round--;
            return;
        }
        $this->cooldown = time() + self::COOLDOWN_BEFORE_START;
        $p1->teleport(new Vector3(self::SPAWN1_EVENT_X, self::SPAWN1_EVENT_Y, self::SPAWN1_EVENT_Z));
        $p2->teleport(new Vector3(self::SPAWN2_EVENT_X, self::SPAWN2_EVENT_Y, self::SPAWN2_EVENT_Z));
        $p1->setImmobile();
        $p2->setImmobile();
    }

    public function getStatus(): int{
        return $this->status;
    }

    public function start(): void{
        $this->status = self::STATUS_IN_PROGRESS;
        foreach([$this->p1, $this->p2] as $p){
            if(!$p instanceof Player) $this->end($p);
            $p->setImmobile(false);
            $p->sendMessage(self::PREFIX . "§acDuel started, you can now move!");
            $p->setGamemode(GameMode::SURVIVAL());
            $p->setFlying(false);
            $p->getInventory()->clearAll();
            $this->event->removePlayer($p, false);
            $p->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), Limits::INT32_MAX, 255, false));
        }
    }

    public function end(?TPlayer $looser): void{
        $this->status = self::STATUS_ENDING;
        if($looser instanceof TPlayer && $looser->isOnline()){
            $this->event->addSpectator($looser);
            $looser->getEffects()->clear();
        }
        $winner = ($this->p1 === $looser) ? $this->p2 : $this->p1;
        if($winner instanceof TPlayer && $winner->isOnline()){
            $this->event->addWinner($winner);
            $winner->getEffects()->clear();
        }
        $this->event->startNewDuel();
    }

    /**
     * @return TPlayer[]
     */
    public function getPlayers(): array{
        return [$this->p1, $this->p2];
    }

    public function getPlayer1(): TPlayer
    {
        return $this->p1;
    }

    public function getPlayer2(): TPlayer{
        return $this->p2;
    }

    public function update(): void{

        foreach ($this->getPlayers() as $player) if(!$player->isOnline()) $this->end(null);

        switch ($this->status){
            case self::STATUS_IN_PROGRESS:
                foreach ($this->getPlayers() as $p) {
                    if($p->isInWater()){
                        $this->end($p);
                    }
                }
                break;

            case self::STATUS_STARTING:
                if($this->cooldown - time() >= 0){
                    $this->start();
                }
                break;
        }
    }
}