<?php

namespace sxworz\pets\form;

use onebone\economyapi\EconomyAPI;
use _64FF00\PurePerms\PurePerms;
use jojoe77777\FormAPI\FormAPI;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\ModalForm;
use pocketmine\player\Player;

class Form
{
    public static function mainPetMenu(Player $player)
    {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;

            $pet = Main::getInstance()->pets[$data];
            $group = PurePerms::getInstance()->getUserDataMgr()->getGroup($player);

            if (Main::getInstance()->group[$group->getName()] < Main::getInstance()->group[$pet->getAvailable()]) {
                $available = $pet->getAvailable();
                $player->sendMessage("§4§l»§r§f Купите привилегию §b{$available}§r§f, чтобы купить компаньона §l" . $pet->getName());
                return;
            }

            $name = strtolower($player->getName());

            if (ConfigManager::isBuy($player, $pet) && ConfigManager::isChoosing($player, $pet)) {
                $player->sendMessage("§a§l»§r§f Вы отправили своего компаньона гулять!");
                Main::getInstance()->unsetPet($player);
                Main::getInstance()->playerDB->set($name, [
                    "nowPet" => false,
                    "pets" => Main::getInstance()->playerDB->getAll()[$name]["pets"]
                ]);
                Main::getInstance()->playerDB->save();
                return;
            }

            if (ConfigManager::isBuy($player, $pet) && !ConfigManager::isChoosing($player, $pet)) {
                $player->sendMessage("§a§l»§r§f Вы выбрали компаньона §l" . $pet->getName());
                Main::getInstance()->unsetPet($player);
                Main::getInstance()->setPet($player, $player->getLocation(), $pet->getTexture());
                Main::getInstance()->playerDB->set($name, [
                    "nowPet" => $pet->getKey(),
                    "pets" => Main::getInstance()->playerDB->getAll()[$name]["pets"]
                ]);
                Main::getInstance()->playerDB->save();
                return;
            }

            if (!ConfigManager::isBuy($player, $pet)) {
                self::buyPetMenu($player, $pet);
            }
        });

        $form->setTitle("§b§lКомпаньоны");
        
        foreach (Main::getInstance()->pets as $pet) {
            if ($pet->getIcon() == null) {
                $form->addButton("§d§l{$pet->getName()}§r\n{$pet->getTextForForm($player)}");
            } else {
                $form->addButton("§d§l{$pet->getName()}§r\n{$pet->getTextForForm($player)}", 1, $pet->getIcon());
            }
        }

        $player->sendForm($form);
    }

    public static function buyPetMenu(Player $player, Pet $pet)
    {
        $form = new ModalForm(function (Player $player, bool $data) use ($pet) {
            $name = strtolower($player->getName());

            if ($data) {
                $money = EconomyAPI::getInstance()->myMoney($player);
                
                if ($money >= $pet->getPriceWithDiscount($player)) {
                    EconomyAPI::getInstance()->reduceMoney($player, $pet->getPriceWithDiscount($player));
                    $pets = Main::getInstance()->playerDB->getAll()[$name]["pets"];
                    $pets[] = $pet->getKey();
                    $nowPet = Main::getInstance()->playerDB->getAll()[$name]["nowPet"];
                    Main::getInstance()->playerDB->set($name, [
                        "nowPet" => $nowPet,
                        "pets" => $pets
                    ]);
                    Main::getInstance()->playerDB->save();
                    $player->sendMessage("§a§l»§r§f Вы купили компаньона §l" . $pet->getName());
                } else {
                    $need = $pet->getPriceWithDiscount($player) - $money;
                    $player->sendMessage("§4§l»§r§f Вам надо еще {$need}, чтобы купить этого компаньон!");
                }
            }
        });

        $form->setTitle("§2§lПодтверждение покупки");
        $form->setContent("Вы уверены, что хотите купить компаньона §l{$pet->getName()}§r за §l§b{$pet->getPriceWithDiscount($player)}");
        $form->setButton1("§2§lДа");
        $form->setButton2("§4§lНет");
        $player->sendForm($form);
    }
}