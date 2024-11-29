<?php

namespace sxworz\pets\pets;

use _64FF00\PurePerms\PurePerms;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Pet
{
    private string $name;
    private int $price;
    private string $available;
    private string $texture;
    private string $key;
    private ?string $icon;

    public function __construct(string $name, int $price, string $available, string $texture, string $key, ?string $icon = null)
    {
        $this->name = $name;
        $this->price = $price;
        $this->available = $available;
        $this->texture = $texture;
        $this->key = $key;
        $this->icon = $icon;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getDiscount(Player $player): float
    {
        $group = PurePerms::getInstance()->getUserDataMgr()->getGroup($player)->getName();
        $config = Main::getInstance()->getConfig()->getAll();

        if (isset($config["discount"][$group][$this->key])) {
            return (float)$config["discount"][$group][$this->key];
        }

        if (isset($config["parents"][$group])) {
            $needGroup = $config["parents"][$group];
            if (isset($config["discount"][$needGroup][$this->key])) {
                return (float)$config["discount"][$needGroup][$this->key];
            }
        }
        
        return 0;
    }

    public function getPriceWithDiscount(Player $player): int
    {
        $discount = $this->getDiscount($player);
        return (int)($this->price - ($this->price / 100 * $discount));
    }

    public function getAvailable(): string
    {
        return $this->available;
    }

    public function getTexture(): string
    {
        return $this->texture;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTextForForm(Player $player): string
    {
        $group = PurePerms::getInstance()->getUserDataMgr()->getGroup($player);
        $mainInstance = Main::getInstance();
        
        if ($mainInstance->group[$group->getName()] < $mainInstance->group[$this->available]) {
            return sprintf("§4§l»§r§f Доступно с §b§l%s §4§l«", $this->available);
        }

        if (ConfigManager::isChoosing($player, $this)) {
            return "§2§l»§r§f Выбрано §2§l«";
        }

        if (ConfigManager::isBuy($player, $this)) {
            return "§a§l»§r§f Куплено §a§l«";
        }

        $price = $this->getPriceWithDiscount($player);
        $discount = $this->getDiscount($player) > 0 ? sprintf("(-%d%%)", $this->getDiscount($player)) : "";
        return sprintf("§d§l»§r§f Купить за §b§l%d§r§9%s §d§l«", $price, $discount);
    }
}