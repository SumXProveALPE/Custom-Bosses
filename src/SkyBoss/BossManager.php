<?php

namespace SkyBoss;

use SkyBoss\entity\BossEntity;
use pocketmine\{entity\Entity,
    entity\Skin,
    level\Position,
    math\Vector3,
    nbt\tag\ByteArrayTag,
    nbt\tag\CompoundTag,
    nbt\tag\DoubleTag,
    nbt\tag\FloatTag,
    nbt\tag\ListTag,
    nbt\tag\StringTag,
    Server,
    Player,
    utils\TextFormat};
use SkyBoss\entity\types\DevilBossEntity;
use SkyBoss\entity\types\GodBossEntity;
use SkyBoss\entity\types\SuperHumanEntity;

final class BossManager{

    /** @var Main $plugin */
    private $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    /**
     * @return bool
     */
    public function isOn(): bool{
        $arr = $this->plugin->getBossDB()->get("boss");
        return $arr["status"];
    }

    public function start(): void{
        $this->setStatus();
        $defaultLevel = Server::getInstance()->getDefaultLevel();
        $entities = [
            new DevilBossEntity(
                Server::getInstance()->getLevelByName("world") ?? $defaultLevel,
            DevilBossEntity::createBaseNBT(new Vector3(100, 100, 100))),
            new SuperHumanEntity(
                Server::getInstance()->getLevelByName("world") ?? $defaultLevel,
                SuperHumanEntity::createBaseNBT(new Vector3(100,100,100))
            ),
            new GodBossEntity(
                Server::getInstance()->getLevelByName("world") ?? $defaultLevel,
                GodBossEntity::createBaseNBT(new Vector3(100,100,100))
            )
        ];
        /** @var BossEntity $entityToSpawn */
        $entityToSpawn = $entities[array_rand($entities)];
        $entityToSpawn->spawnToAll();
        Server::getInstance()->broadcastMessage(TextFormat::GREEN . "Spawned " . $entityToSpawn->getName());
//        $skinId = $player->getSkin()->getSkinId();
//        $skinData = "";
//
//        if(extension_loaded("gd") || extension_loaded("gd2")){
//            if(file_exists(($path = $this->plugin->getDataFolder() . Settings::getBossSkin()))){
//                $img = @\imagecreatefrompng($path);
//                $byte = "";
//                for($y = 0, $height = imagesy($img); $y < $height; $y++){
//                    for($x = 0, $width = imagesx($img); $x < $width; $x++){
//                        $color = imagecolorat($img, $x, $y);
//                        $byte .= pack("c", ($color >> 16) & 0xFF) //red
//                            . pack("c", ($color >> 8) & 0xFF) //green
//                            . pack("c", $color & 0xFF) //blue
//                            . pack("c", 255 - (($color & 0x7F000000) >> 23)); //alpha
//                    }
//                }
//                $skin = new Skin("Standard_Custom", $byte);
//                if($skin->isValid()){
//                    $skinData = $skin->getSkinData();
//                }else{
//                    $this->plugin->getServer()->getLogger()->critical("Skin is not valid@");
//                }
//            }else{
//                $this->plugin->getServer()->getLogger()->critical(Settings::getBossSkin() . " is not a path!");
//            }
//        }else{
//            $this->plugin->getServer()->getLogger()->critical("You do not have GD installed!");
//        }
//
//        $entityNBT = new CompoundTag("", [
//            new ListTag("Pos", [
//                new DoubleTag("", $player->getX()),
//                new DoubleTag("", $player->getY()),
//                new DoubleTag("", $player->getZ())
//            ]),
//            new ListTag("Rotation", [
//                new FloatTag("", 0),
//                new FloatTag("", 0)
//            ]),
//            new ListTag("Motion", [
//                new DoubleTag("", 0),
//                new DoubleTag("", 0),
//                new DoubleTag("", 0)
//            ]),
//            new CompoundTag("Skin", [
//                new StringTag("Name", $skinId),
//                new ByteArrayTag("Data", ($skinData == "" ? $player->getSkin()->getSkinData() : $skinData))
//            ])
//        ]);
//        /** @var BossEntity $entity */
//        $entity = Entity::createEntity("BossEntity", $player->getLevel(), $entityNBT);
//        $entity->setScale(Settings::getBossScale());
//        $entity->setMaxHealth(Settings::getBossHealth());
//        $entity->setHealth($entity->getMaxHealth());
//        $entity->spawnToAll();
    }

    public function stop(): void{
        $this->setStatus(false);
        foreach($this->plugin->getServer()->getLevels() as $level){
            foreach($level->getEntities() as $entity){
                if($entity instanceof BossEntity){
                    $entity->flagForDespawn();
                }
            }
        }
        Main::getInstance()->getScheduler()->scheduleRepeatingTask(Main::getInstance()->task, 20);
    }

    /**
     * @param bool $status
     */
    private function setStatus(bool $status = true): void{
        $arr = $this->plugin->getBossDB()->get("boss");
        $arr["status"] = $status;
        $this->plugin->getBossDB()->set("boss", $arr);
    }
}
