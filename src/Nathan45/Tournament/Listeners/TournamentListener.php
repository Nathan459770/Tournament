<?php

namespace Nathan45\Tournament\Listeners;

use Nathan45\Tournament\TPlayer;
use Nathan45\Tournament\Utils\DataManager;
use Nathan45\Tournament\Utils\IConfig;
use pocketmine\block\BlockLegacyIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\ItemIds;
use pocketmine\player\GameMode;
use pocketmine\Server;

class TournamentListener implements Listener, IConfig
{
    private Server $server;

    public function __construct()
    {
        $this->server = Server::getInstance();
    }

    public function onLogin(PlayerLoginEvent $event): void{
        $player = $event->getPlayer();
        if(!$player->hasAccount()) $player->createAccount();
    }

    public function onCreation(PlayerCreationEvent $event): void{
        $event->setPlayerClass(TPlayer::class);
    }

    public function onJoin(PlayerJoinEvent $event): void{
        $event->getPlayer()->spawn();
    }

    public function onPlace(BlockPlaceEvent $event): void{
        if(!$event->getPlayer()->isOp()){
            $event->cancel();
        }
    }

    public function onBreak(BlockBreakEvent $event): void{
        if(!$event->getPlayer()->isOp()){
            $event->cancel();
        }
    }

    public function onTap(EntityDamageByEntityEvent $event): void{
        $damager = $event->getDamager();
        if($damager instanceof TPlayer && $event->getEntity() instanceof TPlayer){
            if($damager->getGamemode() === GameMode::ADVENTURE()){
                $event->cancel();
            }
        }
    }

    public function onUseItem(PlayerItemUseEvent $event): void{
        $player = $event->getPlayer();
        $item = $event->getItem();

        if($item->getId() === ItemIds::ENDER_PEARL) $event->cancel();

        $player->sendCustomForm(match ($item->getCustomName()){
            self::ITEM_EVENTS => TPlayer::FORM_EVENT,
            self::ITEM_SPECTATE => TPlayer::FORM_SPECTATE,
            self::ITEM_SETTINGS => TPlayer::FORM_SETTINGS,
            default => -1,
        });
    }

    public function onChat(PlayerChatEvent $event): void{
        Server::getInstance()->broadcastMessage($event->getPlayer()->getRank()->__toString() . $event->getMessage());
        $event->cancel();
    }

    public function onDamage(EntityDamageEvent $event): void{
        if($event->getCause() == EntityDamageEvent::CAUSE_FALL) $event->cancel();
    }

    public function onFood(PlayerExhaustEvent $event): void{
        $event->cancel();
    }

    public function onDrop(PlayerDropItemEvent $event): void{
        $event->cancel();
    }
}