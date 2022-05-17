<?php

namespace Nathan45\Tournament\Events;

use Nathan45\Tournament\Loader;
use Nathan45\Tournament\Tasks\StartEventDelayedTask;
use Nathan45\Tournament\TPlayer;
use Nathan45\Tournament\Utils\IConfig;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;

class Event implements IConfig
{
    const STATUS_PENDING = 0;
    CONST STATUS_IN_PROGRESS = 1;
    const STATUS_ENDING = 2;

    const MAX_PLAYERS = 30;
    const MIN_PLAYERS = 2;

    private int $status = 0;
    private int $type = 0;
    private string $uniqueId;
    private array $players = [];
    private ?Duel $duel = null;
    private array $spectators = [];
    private array $winners = [];
    private int $startDate = 0;
    public int $round = 0;

    public function __construct(private TPlayer $hoster, private World $world)
    {
        $this->uniqueId = uniqid();
        $this->addPlayer($this->hoster);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return "§bSumo Event, §6" . (match ($this->getStatus()){
                self::STATUS_PENDING => "Waiting for players...",
                self::STATUS_IN_PROGRESS => "In progress",
                self::STATUS_ENDING => "Finished",
            }) . ($this->getHoster() instanceof TPlayer ? ", §cHoster: " . $this->getHoster()->getName() : "");
    }

    public function canJoin(): bool{
        return $this->status === self::STATUS_PENDING && count($this->players) < self::MAX_PLAYERS;
    }

    public function getUniqueId(): string{
        return $this->uniqueId;
    }

    public function addPlayer(TPlayer $player): void{
        if(!$this->canJoin()){
            $player->sendMessage(self::PREFIX . "§cSorry, you can no longer join this event!");
            return;
        }

        $player->setGamemode(GameMode::ADVENTURE());
        $player->spawn(TPlayer::SPAWN_SPECTATOR);
        $this->players[] = $player;
        $player->sendMessage(self::PREFIX . "§cYou have joined the event!");
     }

     public function removePlayer(TPlayer $player, bool $eliminate = true): void{
        if($this->isInGame($player)){
            unset($this->players[array_search($player, $this->players, true)]);
            if($eliminate){
                $this->addSpectator($player);
                $player->sendMessage(self::PREFIX . "§cYou have been removed from the event!");

            }
        }
     }

     public function isInGame(TPlayer $player): bool{
        foreach ($this->players as $p) if($p === $player) return true;
        return false;
     }

    public function getPlayers(): array{
        return $this->players;
    }

    public function getHoster(): ?Player{
        return $this->hoster;
    }

    public function start(int $cooldown = -1): void{
        if(gmp_sign($cooldown) === 1){
            Loader::getInstance()->getScheduler()->scheduleDelayedTask(new StartEventDelayedTask($this), $cooldown*20);
            $this->startDate = time() + $cooldown;
            return;
        }

        if(count($this->getPlayers()) < self::MIN_PLAYERS){
            if($this->hoster->isOnline()) $this->hoster->sendMessage("§cEvent closing... more players needed!");
            EventManager::getInstance()->removeEvent($this);
            return;
        }

        $this->status = self::STATUS_IN_PROGRESS;
        $this->broadcast(IConfig::PREFIX . "§cThe event has now started!");

        $this->startNewDuel();
    }


    public function getStartDate(): int
    {
        return $this->startDate - time();
    }

    public function end(): void{
        $this->broadcast(IConfig::PREFIX . "§cThe event has now ended!");
        EventManager::getInstance()->removeEvent($this);
    }

    public function broadcast(string $message): void{
        foreach($this->getAll() as $p) $p->sendMessage(self::PREFIX . $message);
    }

    public function update(): void{
        if($this->duel instanceof Duel){
            $this->duel->update();
        }

        foreach ($this->getAll() as $p) {
            if($p instanceof TPlayer) $p->addScoreboard($this);
        }
    }

    public function getWorld(): World
    {
        return $this->world;
    }

    public function getAll(): array{
        return $this->getWorld()->getPlayers();
    }

    public function choosePlayer(): ?TPlayer{
        if(empty($this->players)){
            $this->end();
            return null;
        }
        $p = $this->players[array_rand($this->players)];
        unset($this->players[array_search($p, $this->players, true)]);
        if(!$p instanceof TPlayer || !$p->isOnline()) return $this->choosePlayer();
        return $p;
    }

    public function getDuel(): ?Duel{
        return $this->duel;
    }

    public function startNewDuel(): void{
        if(count($this->players) <= 1){
            $this->players = array_merge($this->players, $this->winners);
            $this->winners = [];
            if(count($this->players) < 1){
                $this->end();
                return;
            }
        }
        $this->duel = new Duel($this->choosePlayer(), $this->choosePlayer(), $this);
        $this->round++;
    }

    public function addWinner(TPlayer $player, ?TPlayer $looser = null): void{
        $this->winners[] = $player;
        if($looser instanceof TPlayer) $this->addSpectator($looser);
    }

    public function getRound(): int{
        return $this->round;
    }

    public function addSpectator(TPlayer $player, bool $eliminated = false): void{
        $player->spawn(TPlayer::SPAWN_SPECTATOR);
        $this->spectators[] = $player;
        if($eliminated) $player->sendMessage(self::PREFIX . "§cYou lost the duel!");
        $player->sendMessage(self::PREFIX . "§cYou are now spectator!");
    }


}