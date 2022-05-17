<?php

namespace Nathan45\Tournament;

use Nathan45\Tournament\Events\Duel;
use Nathan45\Tournament\Events\Event;
use Nathan45\Tournament\Events\EventManager;
use Nathan45\Tournament\Utils\DataManager;
use Nathan45\Tournament\Utils\FormAPI\CustomForm;
use Nathan45\Tournament\Utils\FormAPI\SimpleForm;
use Nathan45\Tournament\Utils\IConfig;
use Nathan45\Tournament\Utils\Rank;
use Nathan45\Tournament\Utils\ScoreBoardAPI;
use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\Server;
use pocketmine\world\Position;

class TPlayer extends Player implements IConfig
{
    const SPAWN_LOBBY = 0;
    const SPAWN_SPECTATOR = 1;

    const FORM_EVENT = 0;
    const FORM_SPECTATE = 1;
    const FORM_SETTINGS = 2;

    private Rank $rank;
    private DataManager $data;

    public function __construct(Server $server, NetworkSession $session, PlayerInfo $playerInfo, bool $authenticated, Location $spawnLocation, ?CompoundTag $namedtag)
    {
        parent::__construct($server, $session, $playerInfo, $authenticated, $spawnLocation, $namedtag);
        $this->data = DataManager::getInstance();
        $this->rank = new Rank($this, $this->data->getRankFor($this->getName()));
    }

    public function spawn(int $spawn = 0): void
    {
        switch ($spawn){
            case self::SPAWN_SPECTATOR:
                $this->getInventory()->clearAll();
                $this->getArmorInventory()->clearAll();
                $this->setGamemode(GameMode::ADVENTURE());
                $this->server->getWorldManager()->loadWorld(self::EVENT_MAP_NAME);
                $this->teleport(new Position(self::SPAWN_SPECTATOR_X, self::SPAWN_SPECTATOR_Y, self::SPAWN_SPECTATOR_Z, $this->server->getWorldManager()->getWorldByName(self::EVENT_MAP_NAME)));
                break;

            default:
                $this->teleport(new Position(self::SPAWN_X, self::SPAWN_Y, self::SPAWN_Z, $this->getServer()->getWorldManager()->getDefaultWorld()));
                $this->getInventory()->clearAll();
                $this->getInventory()->setContents([
                    0 => VanillaItems::DIAMOND_SWORD()->setCustomName(self::ITEM_EVENTS),
                    4 => VanillaItems::ENDER_PEARL()->setCustomName(self::ITEM_SPECTATE),
                    8 => VanillaItems::CLOCK()->setCustomName(self::ITEM_SETTINGS)
                ]);
                $this->getArmorInventory()->clearAll();
                $this->setGamemode(GameMode::ADVENTURE());
                $api = ScoreBoardAPI::getInstance();
                if($api->hasScore($this)) $api->removeScore($this);
        }
    }

    public function isOp(): bool{
        return $this->getServer()->isOp($this->getName());
    }

    public function sendCustomForm(int $form): void{
        $manager = EventManager::getInstance();

        switch ($form){

            case self::FORM_EVENT:

                $form = new SimpleForm(function (Player $player, $data = null) use ($manager){
                    if($data !== null){
                        switch ($data){
                            case "create":
                                $manager->createEvent($player);
                                break;

                            default:
                                $event = $manager->getEventById($data);
                                if($event instanceof Event){
                                    $event->addPlayer($this);
                                }
                        }
                    }
                });

                $form->setTitle("§7- §cEvents §7-");
                foreach ($manager->getEvents(Event::STATUS_PENDING) as $event) if($event instanceof Event) $form->addButton($event->__toString(), -1, "", $event->getUniqueId());
                $form->addButton("Start Event", -1, "", "create");
                break;

            case self::FORM_SPECTATE:
                if(count($manager->getEvents(Event::STATUS_IN_PROGRESS)) < 1){
                    $this->sendMessage(self::PREFIX . "§cThere aren't any event at the moment!");
                    return;
                }
                $form = new SimpleForm(function (TPlayer $player, $data) use($manager){
                    if($data !== null){
                        $event = $manager->getEventById($data);
                        if($event instanceof Event){
                            $event->addSpectator($player);
                        }
                    }
                });

                $form->setTitle("§7- §cSpectate §7-");
                foreach ($manager->getEvents(Event::STATUS_IN_PROGRESS) as $event) if($event instanceof Event) $form->addButton($event->__toString(), -1, "", $event->getUniqueId());
                break;

            case self::FORM_SETTINGS:
                $form = new CustomForm(function (Player $player, $data){
                    if($data !== null){
                        DataManager::getInstance()->setScoreboardFor($player->getName(), $data[0]);
                        $player->sendMessage(self::PREFIX . ($data[0] ? "§aYou have displayed the scoreboard." : "§aYou have hidden the scoreboard"));
                    }
                });

                $form->setTitle("§7- §cSettings §7-");
                $form->addToggle("Scoreboard", DataManager::getInstance()->getScoreboardFor($this->getName()));
                break;

            default:
                return;
        }
        $this->sendForm($form);
    }

    public function addScoreboard(Event $event): void{
        $api = ScoreBoardAPI::getInstance();
        if(!DataManager::getInstance()->getScoreboardFor($this->getName())){
            if($api->hasScore($this)) $api->removeScore($this);
            return;
        }
        $lines = [
            self::LINE_0,
            str_replace("{round}", $event->getRound(), self::LINE_1),
            self::LINE_2,
            (($event->getDuel() instanceof Duel) ? str_replace(["{player}", "{player2}"], [$event->getDuel()->getPlayer1()->getName(), $event->getDuel()->getPlayer2()->getName()],self::LINE_3) : str_replace("{seconds}", $event->getStartDate(), self::LINE_WAITING))
        ];
        $api->sendScore($this, self::SCOREBOARD_TITLE);
        $api->setLines($this, $lines);
    }

    public function isInWater(): bool{
        $id = $this->getWorld()->getBlock($this->getPosition()->floor())->getId();
        return $id === BlockLegacyIds::WATER || $id === BlockLegacyIds::FLOWING_WATER;
    }

    public function createAccount(): void{
        $this->data->create($this);
    }

    public function hasAccount(): bool{
        return isset($this->data->data[$this->getName()]);
    }

    public function getRank(): Rank{
        return $this->rank;
    }

    public function setRank(int $id = 0): void{
        $this->rank = new Rank($this, $id);
    }

}