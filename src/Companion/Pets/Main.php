<?php

namespace FactoryPlugins\Pets;

use _64FF00\PurePerms\PurePerms;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\World;

class Main extends PluginBase
{
    /** @var array */
    public array $petsData = [];
    private static Main $instance;
    public array $group = [];

    /** @var Pet[] */
    public array $pets = [];
    public Config $playerDB;

    protected function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->playerDB = new Config($this->getDataFolder() . "playersDB.yml", Config::YAML);
        
        EntityFactory::getInstance()->register(PetEntity::class, function(World $world, CompoundTag $nbt): PetEntity {
            return new PetEntity(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt, null);
        }, ['PetEntity']);

        $this->saveResources();
        
        $this->loadGroups();
        $this->loadPets();
    }

    protected function onLoad(): void
    {
        self::$instance = $this;
    }

    public static function getInstance(): Main
    {
        return self::$instance;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if ($sender instanceof Player) {
            Form::mainPetMenu($sender);
        }
        return true;
    }

    public function unsetPet(Player $player): void
    {
        if (isset($this->petsData[spl_object_hash($player)])) {
            $entity = ConfigManager::getPetEntity($player);
            if ($entity !== null && $entity instanceof PetEntity) {
                if (!$entity->isFlaggedForDespawn()) {
                    $entity->flagForDespawn();
                    $entity->close();
                }
                unset($this->petsData[spl_object_hash($player)]);
            }
        }
    }

    public function setPet(Player $player, Location $pos, string $texture): void
    {
        $newPos = new Location($pos->x, $pos->y + 2.1, $pos->z, $pos->world, $player->getLocation()->getYaw(), $player->getLocation()->getPitch());
        $nbt = self::createBaseNBT($newPos, null, $player->getLocation()->getYaw(), $player->getLocation()->getPitch());

        $petEntity = new PetEntity($newPos, $player->getSkin(), $nbt, $player);
        $petEntity->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.2);
        $petEntity->getNetworkProperties()->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, new Vector3(0.485, 1.45, 0));

        $petEntity->setCanSaveWithChunk(false);
        $petEntity->setNameTagAlwaysVisible(false);
        $petEntity->setScale(0.56);
        $petEntity->setImmobile();
        $petEntity->spawnToAll();
        $petEntity->setSkin($this->getModel($texture));
        $petEntity->sendSkin();

        $eid = $petEntity->getId();
        
        $link = new SetActorLinkPacket();
        $link->link = new EntityLink($player->getId(), $eid, EntityLink::TYPE_RIDER, true, false);
        $petEntity->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, true);

        $this->getServer()->broadcastPackets(Server::getInstance()->getOnlinePlayers(), [$link]);
        $this->petsData[spl_object_hash($player)] = [
            "entity" => $petEntity,
            "eid" => $eid
        ];
    }

    public static function createBaseNBT(Vector3 $pos, ?Vector3 $motion = null, float $yaw = 0.0, float $pitch = 0.0): CompoundTag
    {
        return CompoundTag::create()
            ->setTag("Pos", new ListTag([
                new DoubleTag($pos->x),
                new DoubleTag($pos->y),
                new DoubleTag($pos->z)
            ]))
            ->setTag("Motion", new ListTag([
                new DoubleTag($motion !== null ? $motion->x : 0.0),
                new DoubleTag($motion !== null ? $motion->y : 0.0),
                new DoubleTag($motion !== null ? $motion->z : 0.0)
            ]))
            ->setTag("Rotation", new ListTag([
                new FloatTag($yaw),
                new FloatTag($pitch)
            ]));
    }

    public function getModel(string $tex): Skin
    {
        $texture = $this->getDataFolder() . "/skins/{$tex}.png";
        $img = imagecreatefrompng($texture);
        if ($img === false) {
            throw new \RuntimeException("Could not create image from texture");
        }
        
        $skinBytes = "";
        for ($y = 0; $y < 64; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgba = imagecolorat($img, $x, $y);
                $a = ((~($rgba >> 24)) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $skinBytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        imagedestroy($img);

        return new Skin("Pet", $skinBytes, "", "geometry.pet", file_get_contents($this->getDataFolder() . "pet.geo.json"));
    }

    private function saveResources(): void
    {
        $this->saveResource("config.yml");
        $this->saveResource("pet.geo.json");
        $this->saveDefaultConfig();
    }

    private function loadGroups(): void
    {
        $i = 0;
        foreach (PurePerms::getInstance()->getGroups() as $group) {
            $this->group[$group->getName()] = $i;
            $i++;
        }
    }

    private function loadPets(): void
    {
        foreach ($this->getConfig()->getAll()["pets"] as $key => $value) {
            $this->pets[] = new Pet($value["name"], $value["price"], $value["available"], $value["texture"], strtolower($key), $value["icon"]);
        }
    }
}