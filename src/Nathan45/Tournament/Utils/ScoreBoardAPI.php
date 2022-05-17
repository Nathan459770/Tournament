<?php
/*
__    __   ___   __   _   _____   _____  __    __
\ \  / /  /   | |  \ | | /  _  \ /  _  \ \ \  / /
 \ \/ /  / /| | |   \| | | | | | | | | |  \ \/ /
  \  /  / / | | | |\   | | | | | | | | |   }  {
  / /  / /  | | | | \  | | |_| | | |_| |  / /\ \
 /_/  /_/   |_| |_|  \_| \_____/ \_____/ /_/  \_\

APIs name: ScoreBoardAPI
Author: Yanoox
Plugin's api: 4.0.0
For: everybody :)
edited by: Nathan45
 */
namespace Nathan45\Tournament\Utils;

use BadFunctionCallException;
use OutOfBoundsException;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

class ScoreBoardAPI{

    use SingletonTrait;

    /**
     * Contains the scoreboard of the players
     *
     * @var string[]
     */
    public array $scoreboards = [];

    /**
     * Contains strings containing in each line the player's score (if he has one)
     *
     * @var string[]
     */
    public array $lineScore = [];

    /**
     * Add a scoreboard to player, this option is mandatory: you can't set a score to a player without a scoreboard
     *
     * @param Player $player
     * @param string $displayName
     * @param int $slotOrder
     * @param string $displaySlot
     * @param string $objectiveName
     * @param string $criteriaName
     * @return void
     */
    public function sendScore(Player $player, string $displayName, int $slotOrder = SetDisplayObjectivePacket::SORT_ORDER_ASCENDING, string $displaySlot = SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR, string $objectiveName = "objective", string $criteriaName = "dummy"): void{
        if ($player->isConnected()) {

            if ($this->hasScore($player)) {
                $this->removeScore($player);
            }

            $packet = new SetDisplayObjectivePacket();
            $packet->displaySlot = $displaySlot;
            $packet->objectiveName = $objectiveName;
            $packet->displayName = $displayName;
            $packet->criteriaName = $criteriaName;
            $packet->sortOrder = $slotOrder;
            $player->getNetworkSession()->sendDataPacket($packet);

            $this->scoreboards[mb_strtolower($player->getName())] = $objectiveName;
            $this->lineScore[mb_strtolower($player->getName())][0] = $objectiveName;
        }
    }

    /**
     * Sets a line to the player's score
     *
     * @param Player $player
     * @param int $line
     * @param string $message
     * @param int $type
     * @return void
     */
    public function setScoreLine(Player $player, int $line, string $message, int $type = ScorePacketEntry::TYPE_FAKE_PLAYER): void{
        if ($player->isConnected()) {
            if (!$this->hasScore($player)) {
                throw new BadFunctionCallException("Cannot set the line : the player's scoreboard has not been found");
            }

            if ($this->isNotLineValid($line)) {
                throw new OutOfBoundsException("$line isn't between 1 and 15");
            }

            $entry = new ScorePacketEntry;
            $entry->objectiveName = $this->scoreboards[mb_strtolower($player->getName())] ?? "objective";
            $entry->type = $type;
            $entry->customName = $message;
            $entry->score = $line;
            $entry->scoreboardId = $line;

            $packet = new SetScorePacket();
            $packet->type = $packet::TYPE_CHANGE;
            $packet->entries[] = $entry;
            $player->getNetworkSession()->sendDataPacket($packet);

            $this->lineScore[mb_strtolower($player->getName())][$line] = $message;
        }
    }

    /**
     * Edit a line of a player
     *
     * @param Player $player
     * @param int $line
     * @return string
     */
    public function getLineScore(Player $player, int $line) : string{
        if ($player->isConnected()) {
            if (!$this->hasScore($player)) {
                throw new BadFunctionCallException("Cannot get the line : the player's scoreboard has not been found");
            }
            return $this->lineScore[mb_strtolower($player->getName())][$line];
        }
        return false;
    }

    /**
     * @param Player $player
     * @param int $line
     * @param string|float $search
     * @param string|float $replace
     * @return void
     */
    public function editScoreLine(Player $player, int $line, string|float $search, string|float $replace)
    {
        if ($player->isConnected()) {
            if (!$this->hasScore($player)) {
                throw new BadFunctionCallException("Cannot edit the line : the player's scoreboard has not been found");
            }
            if ($this->isNotLineValid($line)) {
                throw new OutOfBoundsException("$line isn't between 1 and 15");
            }

            $this->removeLine($player, $line);

            $entry = new ScorePacketEntry();
            $entry->objectiveName = $this->scoreboards[mb_strtolower($player->getName())] ?? "objective";
            $entry->customName = str_replace($search, $replace, $this->getLineScore($player, $line));
            $entry->score = $line;
            $entry->scoreboardId = $line;
            $entry->type = $entry::TYPE_FAKE_PLAYER;

            $packet = new SetScorePacket();
            $packet->type = SetScorePacket::TYPE_CHANGE;
            $packet->entries[] = $entry;
            $player->getNetworkSession()->sendDataPacket($packet);

            $this->lineScore[mb_strtolower($player->getName())][$line] = str_replace($search, $replace, $this->getLineScore($player, $line));
        }
    }

    /**
     * Edit a line of a player
     *
     * @param Player $player
     * @param int $line
     * @return void
     */
    public function removeLine(Player $player, int $line) : void{
        if ($player->isConnected()) {
            $packet = new SetScorePacket();
            $packet->type = SetScorePacket::TYPE_REMOVE;

            $entry = new ScorePacketEntry();
            $entry->objectiveName = $this->scoreboards[mb_strtolower($player->getName())] ?? "objective";
            $entry->score = $line;
            $entry->scoreboardId = $line;
            $entry->customName = $this->getLineScore($player, $line);
            $packet->entries[] = $entry;

            $player->getNetworkSession()->sendDataPacket($packet);
        }
    }


    /**
     * Remove the scoreboard from the player
     *
     * @param Player $player
     * @return void
     */
    public function removeScore(Player $player): void{
        if ($player->isConnected() && $this->hasScore($player)) {
            $objectiveName = $this->scoreboards[mb_strtolower($player->getName())] ?? "objective";

            $packet = new RemoveObjectivePacket();
            $packet->objectiveName = $objectiveName;
            $player->getNetworkSession()->sendDataPacket($packet);

            unset($this->scoreboards[mb_strtolower($player->getName())]);
            unset($this->lineScore[mb_strtolower($player->getName())]);
        }
    }

    /**
     * Return a boolean if the player has a score or not
     *
     * @param Player $player
     * @return bool
     */
    public function hasScore(Player $player): bool{
        return isset($this->scoreboards[mb_strtolower($player->getName())]);
    }

    public function setLines(Player $player, array $lines, int $type = ScorePacketEntry::TYPE_FAKE_PLAYER): void{
        foreach ($lines as $line => $message) {
            $this->setScoreLine($player, $line, $message, $type);
        }
    }

    public function isNotLineValid(int $line): bool
    {
        return $line < 0 || $line > 15;
    }
}