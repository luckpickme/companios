<?php

namespace _64FF00\PurePerms\event;

use _64FF00\PurePerms\PPGroup;
use _64FF00\PurePerms\PurePerms;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\player\IPlayer;
use pocketmine\Server;
use pocketmine\world\World;

class PPRankExpiredEvent extends PluginEvent{
	/*
		PurePerms by 64FF00 (Twitter: @64FF00)

		  888  888    .d8888b.      d8888  8888888888 8888888888 .d8888b.   .d8888b.
		  888  888   d88P  Y88b    d8P888  888        888       d88P  Y88b d88P  Y88b
		888888888888 888          d8P 888  888        888       888    888 888    888
		  888  888   888d888b.   d8P  888  8888888    8888888   888    888 888    888
		  888  888   888P "Y88b d88   888  888        888       888    888 888    888
		888888888888 888    888 8888888888 888        888       888    888 888    888
		  888  888   Y88b  d88P       888  888        888       Y88b  d88P Y88b  d88P
		  888  888    "Y8888P"        888  888        888        "Y8888P"   "Y8888P"
	*/

	protected $player;

	protected $levelName;

	/**
	 * @param PurePerms $plugin
	 * @param IPlayer   $player
	 * @param PPGroup   $group
	 * @param           $levelName
	 */
	public function __construct(PurePerms $plugin, IPlayer $player, $levelName){
		parent::__construct($plugin);

		$this->player = $player;
		$this->levelName = $levelName;
	}

	/**
	 * @return World
	 */
	public function getLevel(){
		return Server::getInstance()->getWorldManager()->getWorldByName($this->levelName);
	}

	/**
	 * @return string
	 */
	public function getLevelName(){
		return $this->levelName;
	}

	/**
	 * @return IPlayer
	 */
	public function getPlayer(){
		return $this->player;
	}
}