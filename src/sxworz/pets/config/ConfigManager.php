<?php

namespace sxworz\pets\config;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class ConfigManager
{
    /**
     * @param Player $player
     * @param Pet $pet
     * @return bool
     */
    public static function isBuy(Player $player, Pet $pet): bool
    {
        $name = strtolower($player->getName());
        $petKey = $pet->getKey();
        return in_array($petKey, Main::getInstance()->playerDB->getAll()[$name]["pets"] ?? []);
    }

    public static function getPetEntity(Player $player): ?PetEntity
    {
        $playerHash = spl_object_hash($player);
        if (isset(Main::getInstance()->petsData[$playerHash])) {
            $entity = Main::getInstance()->petsData[$playerHash]["entity"] ?? null;
            if ($entity instanceof PetEntity) {
                return $entity;
            }
        }
        return null;
    }

    public static function getPet(Player $player): ?Pet
    {
        $name = strtolower($player->getName());
        $petName = Main::getInstance()->playerDB->getAll()[$name]["nowPet"] ?? null;
        if ($petName === false || $petName === null) return null;

        foreach (Main::getInstance()->pets as $pet) {
            if ($pet->getKey() === $petName) {
                return $pet;
            }
        }
        return null;
    }

    public static function isChoosing(Player $player, Pet $pet): bool
    {
        return $pet->getKey() === self::getPet($player)?->getKey();
    }

    /**
     * @param Player $player
     * @return void
     * @throws \JsonException
     */
    public static function addPlayer(Player $player): void
    {
        $name = strtolower($player->getName());
        Main::getInstance()->playerDB->set($name, [
            "nowPet" => null,
            "pets" => []
        ]);
        Main::getInstance()->playerDB->save();
    }

    /**
     * @param Player $player
     * @return bool
     */
    public static function exists(Player $player): bool
    {
        $name = strtolower($player->getName());
        return Main::getInstance()->playerDB->exists($name);
    }
}