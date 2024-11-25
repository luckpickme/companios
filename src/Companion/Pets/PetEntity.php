<?php

namespace FactoryPlugins\Pets;

use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class PetEntity extends Human
{
    private ?Player $player;

    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null, ?Player $player = null)
    {
        $this->player = $player;
        parent::__construct($location, $skin, $nbt);
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();
    }

    public function onUpdate(int $currentTick): bool
    {
        if ($this->player !== null) {
            $this->setRotation($this->player->getLocation()->getYaw(), $this->player->getLocation()->getPitch());
        }
        return parent::onUpdate($currentTick);
    }
}