<?php

namespace _64FF00\PurePerms;

use _64FF00\PurePerms\event\PPGroupChangedEvent;
use _64FF00\PurePerms\event\PPRankExpiredEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PPListener implements Listener{
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

	private $plugin;

	/**
	 * @param PurePerms $plugin
	 */
	public function __construct(PurePerms $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @param PPGroupChangedEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onGroupChanged(PPGroupChangedEvent $event){
		$player = $event->getPlayer();

		$this->plugin->updatePermissions($player);
	}

	/**
	 * @param EntityTeleportEvent $event
	 *
	 * @priority MONITOR
	 */
	public function onLevelChange(EntityTeleportEvent $event){
		$from = $event->getFrom();
		$to = $event->getTo();

		if($from->getWorld()->getFolderName() === $to->getWorld()->getFolderName()) return;

		$player = $event->getEntity();
		if($player instanceof Player){
			$this->plugin->updatePermissions($player, $to->getWorld()->getFolderName());
		}
	}

	public function onPlayerCommand(CommandEvent $event){
		$command = $event->getCommand();
		$player = $event->getSender();
		if(!$player instanceof Player)return;
		if(!$this->plugin->getNoeulAPI()->isAuthed($player)){
			$event->cancel();
			if($command === "ppsudo" || $command === "help") {
				$this->plugin->getServer()->dispatchCommand($player, $command);
			}else{
				$this->plugin->getNoeulAPI()->sendAuthMsg($player);
			}
		}else{
			$disableOp = $this->plugin->getConfigValue("disable-op");
			if($disableOp && $command === "op"){
				$event->cancel();
				$player->sendMessage(new Translatable(TextFormat::RED . "%commands.generic.permission"));
			}
		}
	}	

	/**
	 * @param PlayerLoginEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onPlayerLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();

		$this->plugin->registerPlayer($player);

		if($this->plugin->getNoeulAPI()->isNoeulEnabled())
			$this->plugin->getNoeulAPI()->deAuth($player);

		if(!$this->plugin->getNoeulAPI()->isAuthed($player))
			$this->plugin->getNoeulAPI()->sendAuthMsg($player);
	}

	/**
	 * @param PlayerQuitEvent $event
	 *
	 * @priority HIGHEST
	 */
	public function onPlayerQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();

		$this->plugin->unregisterPlayer($player);
	}

	/**
	 * @param PPRankExpiredEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onRankExpired(PPRankExpiredEvent $event){
		$player = $event->getPlayer();

		$this->plugin->setGroup($player, $this->plugin->getDefaultGroup());
	}
}
