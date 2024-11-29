<?php

namespace _64FF00\PurePerms\scorehud;

use pocketmine\player\Player;

use pocketmine\event\Listener;

use Ifera\ScoreHud\event\TagsResolveEvent;
use Ifera\ScoreHud\event\PlayerTagsUpdateEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;

use _64FF00\PurePerms\PurePerms;
use _64FF00\PurePerms\event\PPGroupChangedEvent;

class PurePermsScore implements Listener{

    /**
     * @param Player $player
     * @return string
     */
    public function getPlayerRank(Player $player): string{
		$group = PurePerms::getInstance()->getUserDataMgr()->getData($player)["group"];
		return $group ?? "No Rank";
	}

    /**
     * @param Player $player
     * @return string
     */
	public function getPrefix(Player $player): string{
		$prefix = PurePerms::getInstance()->getUserDataMgr()->getNode($player, "prefix");
		return (($prefix === null) || ($prefix === "")) ? "No Prefix" : (string) $prefix;
	}

    /**
     * @param Player $player
     * @return string
     */
	public function getSuffix(Player $player): string{
		$suffix = PurePerms::getInstance()->getUserDataMgr()->getNode($player, "suffix");
		return (($suffix === null) || ($suffix === "")) ? "No Suffix" : (string) $suffix;
	}

    /**
     * @param PlayerJoinEvent $event
     * @return void
     */
    public function onJoin(PlayerJoinEvent $event): void{
		$player = $event->getPlayer();
		if(is_null($player) || !$player->isOnline()){
			return;
		}
		$this->sendUpdate($player);
	}

    /**
     * @param PPGroupChangedEvent $event
     * @return void
     */
	public function onGroupChange(PPGroupChangedEvent $event): void{
		$player = $event->getPlayer();

		if(!$player instanceof Player || !$player->isOnline()){
			return;
		}

		$this->sendUpdate($player);
	}

	// no better way to detect when the suffix or prefix of a player changes
    /**
     * @param PlayerChatEvent $event
     * @return void
     */
	public function onPlayerChat(PlayerChatEvent $event): void{
		$this->sendUpdate($event->getPlayer());
	}

    /**
     * @param Player $player
     * @return void
     */
    private function sendUpdate(Player $player): void{
		(new PlayerTagsUpdateEvent($player, [
			new ScoreTag("ppscore.rank", $this->getPlayerRank($player)),
			new ScoreTag("ppscore.prefix", $this->getPrefix($player)),
			new ScoreTag("ppscore.suffix", $this->getSuffix($player))
		]))->call();
	}

    /**
     * @param TagsResolveEvent $event
     * @return void
     */
    public function onTagResolve(TagsResolveEvent $event): void{
		$player = $event->getPlayer();
		$tag = $event->getTag();
		$tags = explode('.', $tag->getName(), 2);
		$value = "";
		if($tags[0] !== 'ppscore' || count($tags) < 2){
			return;
		}
		switch($tags[1]){
			case "rank":
				$value = $this->getPlayerRank($player);
			break;
			case "prefix":
				$value = $this->getPrefix($player);
			break;
			case "suffix":
				$value = $this->getSuffix($player);
			break;
		}
		$tag->setValue($value);
	}
}