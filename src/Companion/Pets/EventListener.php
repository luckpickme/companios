<?php

namespace FactoryPlugins\Pets;

use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class EventListener implements Listener
{
    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        if (!ConfigManager::exists($player)) {
            ConfigManager::addPlayer($player);
        }
        
        $pet = ConfigManager::getPet($player);
        if ($pet !== null) {
            Main::getInstance()->setPet($player, $player->getLocation(), $pet->getTexture());
        }
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        Main::getInstance()->unsetPet($player);
    }

    public function onTeleport(EntityTeleportEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            if ($event->getFrom()->getWorld()->getId() === $event->getTo()->getWorld()->getId()) {
                return;
            }

            $pet = ConfigManager::getPet($entity);
            if ($pet !== null) {
                Main::getInstance()->unsetPet($entity);
                Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($entity, $pet) {
                    Main::getInstance()->setPet($entity, $entity->getLocation(), $pet->getTexture());
                }), 40);
            }
        }
    }
}