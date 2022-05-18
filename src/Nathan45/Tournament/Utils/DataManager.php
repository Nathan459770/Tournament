<?php

namespace Nathan45\Tournament\Utils;

use Nathan45\Tournament\Loader;
use Nathan45\Tournament\Tasks\LoadItTask;
use Nathan45\Tournament\Tasks\SendQueryTask;
use Nathan45\Tournament\TPlayer;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class DataManager
{
    use SingletonTrait;

    const RANK = 0;
    const SCOREBOARD = 1;

    const SCOREBOARD_DISABLED = 0;
    const SCOREBOARD_ENABLED = 1;

    private bool $enabled = false;
    private \SQLite3 $db;
    private string $path;
    private Server $server;

    public array $data = [];

    public function __construct()
    {
        $this->server = Server::getInstance();
        $this->path = Loader::getInstance()->getDataFolder() . "rank.db";
        if(!$this->isEnabled()){
            $this->init();
        }
    }

    public function init(): void{
        $db = new \SQLite3($this->path);
        $db->query("CREATE TABLE IF NOT EXISTS `ranks` (`username` VARCHAR(255), `rank` int, `scoreboard` int)");
        $this->db = $db;
        $this->db->close();
        $this->server->getAsyncPool()->submitTask(new LoadItTask($this->path));
    }

    public function sendQuery(string $query): void{
        $this->server->getAsyncPool()->submitTask(new SendQueryTask($this->path, $query));
    }

    public function isEnabled(): bool{
        return $this->enabled;
    }

    public function setAllData(array $data): void{
        $this->data = $data;
    }

    public function getAllData(): array{
        return $this->data;
    }

    public function getDataFor(string $player): array{
        return $this->data[$player] ?? [];
    }

    public function getRankFor(string $player): int{
        return $this->getDataFor($player)[self::RANK] ?? 0;
    }

    public function getScoreboardFor(string $player): bool{
        return boolval($this->getDataFor($player)[self::SCOREBOARD] ?? 1);
    }

    public function setScoreboardFor(string $player, bool $scoreboard = true): void{
        $int = $scoreboard ? 1 : 0;
        if(!isset($this->data[$player])) $this->create($player);
        $this->data[$player][self::SCOREBOARD] = $int;
        $this->sendQuery("UPDATE `ranks` SET `rank` = '$int' WHERE `username` = '$player'");
    }

    public function create(TPlayer|string $player): void{
        if($player instanceof TPlayer) $player = $player->getName();
        $this->data[$player] = [Rank::PLAYER, self::SCOREBOARD_ENABLED];
        $this->sendQuery("INSERT INTO `ranks` (`username`, `rank`, `scoreboard`) VALUES ('$player', '" . Rank::PLAYER . "', '" . DataManager::SCOREBOARD_ENABLED . "')");
    }

    public function setRankFor(string $player, int $rank = 0): void{
        $this->data[$player][self::RANK] = $rank;
        if(($p = Server::getInstance()->getPlayerByPrefix($player)) instanceof TPlayer) $p->setRank($rank);
        $this->sendQuery("UPDATE `ranks` SET `rank` = '$rank' WHERE `username` = '$player'");
    }
}