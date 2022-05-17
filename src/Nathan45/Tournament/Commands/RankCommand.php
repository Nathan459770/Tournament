<?php

namespace Nathan45\Tournament\Commands;

use Nathan45\Tournament\TPlayer;
use Nathan45\Tournament\Utils\DataManager;
use Nathan45\Tournament\Utils\FormAPI\CustomForm;
use Nathan45\Tournament\Utils\IConfig;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;

class RankCommand extends Command implements IConfig
{
    public function __construct()
    {
        parent::__construct("rank", "Rose - Manages ranks", "/rank <player:string> <int:rank>");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(Server::getInstance()->isOp($sender->getName())){
            if($sender instanceof TPlayer){
                $this->sendForm($sender);
            }else{
                if(isset($args[0]) && isset($args[1]) && is_int($args[1])){
                    DataManager::getInstance()->setRankFor($args[0], $args[1]);
                    $sender->sendMessage("Successful");
                }else{
                    $sender->sendMessage($this->usageMessage);
                }
            }
        }else{
            $sender->sendMessage(self::PREFIX . "§cYou don't have the permission to manage ranks.");
        }
    }

    public function sendForm(TPlayer $player): void{
        $players = [];
        $ranks = ["Player", "Premium", "Builder", "Developer", "Executive"];
        foreach (Server::getInstance()->getOnlinePlayers() as $p)$players[] = $p->getName();

        $form = new CustomForm(function (TPlayer $player, $data) use($players, $ranks){
            if($data === null) return;

            $target = (empty($data[1])) ? $players[$data[0]] : $data[1];
            DataManager::getInstance()->setRankFor($target, $data[2]);
            $player->sendMessage(self::PREFIX . "§aYou have awarded {$target} the rank of {$ranks[$data[2]]}!");
        });
        $form->setTitle("§7- §cRanks §7-");
        $form->addDropdown("Select :", $players);
        $form->addInput("Or enter his name : \n(it can be an offline player)");
        $form->addDropdown("Select his rank", $ranks, 0);
        $player->sendForm($form);
    }

}