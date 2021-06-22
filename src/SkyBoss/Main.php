<?php

namespace SkyBoss;

use SkyBoss\command\BossCommand;
use SkyBoss\command\SpawnBossCommand;
use SkyBoss\entity\BossEntity;
use SkyBoss\entity\types\GodBossEntity;
use SkyBoss\task\ScoreboardTask;
use pocketmine\{entity\Entity,
    entity\Skin,
    item\Item,
    plugin\PluginBase,
    scheduler\ClosureTask,
    Server,
    Player,
    utils\Config,
    utils\TextFormat};
use SkyBoss\task\SpawnBossTask;

class Main extends PluginBase{

    /** @var int $scoreboardIds */
    public static $scoreboardIds = 1;

    /** @var Config $config */
    private $config;
    /** @var Config $bossDB */
    private $bossDB;

    /** @var BossManager $bossMngr */
    protected $bossMngr;
    /** @var Session[] $sessions */
    protected $sessions = [];

    /** @var self $object */
    protected static $object;
    /**
     * @var string
     */
    private $skinFolder;
    public $task;
    /**
     * @var string
     */
    private $devilSkin;
    /**
     * @var string
     */
    private $godSkin;
    /**
     * @var string
     */
    private $superhumanSkin;

    public function onLoad(){
        self::$object = $this;
    }

    /**
     * @return Main
     */
    public static function getInstance(): self{
        return self::$object;
    }

    public function onEnable(){
        $this->saveResource("Devil.png");
        $this->saveResource("God.png");
        $this->saveResource("SuperHuman.png");
        $this->task = new SpawnBossTask();
        $this->getScheduler()->scheduleRepeatingTask($this->task, 20);
        $this->devilSkin = $this->getDataFolder() . "Devil.png";
        $this->godSkin = $this->getDataFolder() . "God.png";
        $this->superhumanSkin = $this->getDataFolder() . "SuperHuman.png";
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
            "[NOTE] SCOREBOARD TITLE",
            "scoreboardTitle" => TextFormat::RED . " Boss Event ",
            "[NOTE] SCOREBOARD CONTENT, AVAILABLE FORMAT: {life}, {coords}, {level}",
            "scoreboardContent" => [
                1 => "SkyNetwork",
                2 => "Boss Event",
                3 => "Life: {life}",
                4 => "Coordinates: {coords}",
                5 => "Level: {level}"
            ],
            "[NOTE] BOSS SPEED",
            "speed" => 1.5,
            "[NOTE] BOSS SCALE (SIZE)",
            "scale" => 6,
            "[NOTE] BOSS DAMAGE, YOU CAN REMOVE OR ADD MORE DAMAGE FOR MORE CHANCE",
            "damage" => [
                4,
                5,
                6,
                7
            ],
            "[NOTE] BOSS DROPS, CUSTOM NAME DOESN'T NEED TO BE SET, ID CAN BE NAME OR NUMBER, IF DAMAGE IS 0 THEN YOU DON'T NEED TO SET IT, IF AMOUNT IS 1 YOU DON'T NEED TO SET IT",
            "drops" => [
                [
                    "name" => "Diamonds Omg",
                    "id" => Item::DIAMOND_BLOCK,
                    "damage" => 0,
                    "amount" => 3,
                    "enchantments" => [
                        "protection" => 5
                    ]
                ]
            ],
            "[NOTE] BOSS CUSTOM SKIN, MUST BE PATH OR LINK, IF INVALID, WILL THROW ERROR",
            "skin" => "path",
            "[NOTE] EFFECT WHEN BOSS HIT PLAYER, DON'T PUT ANYTHING IF YOU WANT NO EFFECT, DURATION IS IN SECONDS",
            "effect" => [
                [
                    "name" => "poison",
                    "amplifier" => 1,
                    "duration" => 5
                ]
            ],
            "[NOTE] BOSS MAX HEALTH",
            "health" => 100,
            "[NOTE] BOSS HEALTH AND NAMETAG FORMAT",
            "nametag" => [
                "display" => "Boss Entity\n" . TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . "{bar}" . TextFormat::DARK_GRAY . "]",
                "barLength" => 80
            ],
            "[NOTE] BOSS ATTACK DISTANCE",
            "distance" => 2,
            "[NOTE] BOSS KNOCKBACK FORCE, NORMAL IS 1",
            "knockback" => 1
        ]);
        $this->bossDB = new Config($this->getDataFolder() . "bossDB.json", Config::JSON, [
            "boss" => [
                "status" => false
            ]
        ]);

        $this->bossMngr = new BossManager($this);

        Settings::init();

        Entity::registerEntity(BossEntity::class, true, ["BossEntity", "minecraft:bossentity"]);
        Entity::registerEntity(GodBossEntity::class, true, ["GodEntity", "minecraft:godentity"]);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->registerAll("ayzrix", [new BossCommand($this), new SpawnBossCommand()]);
//        $this->getScheduler()->scheduleRepeatingTask(new ScoreboardTask($this), 1);
    }

    public function onDisable(){
        $this->config->save();
        $this->bossDB->save();
    }

    /**
     * @return Config
     */
    public function getConfig(): Config{
        return $this->config;
    }

    /**
     * @return Config
     */
    public function getBossDB(): Config{
        return $this->bossDB;
    }

    /**
     * @return BossManager
     */
    public function getBossManager(): BossManager{
        return $this->bossMngr;
    }

    /**
     * @param Player $player
     */
    public function openSession(Player $player): void{
        $this->sessions[$player->getId()] = new Session($player, $this);
    }

    public static function getSkinFromPNG(string $path): string{
        $img = @imagecreatefrompng($path);
        $skinbytes = "";
        $s = (int)@getimagesize($path)[1];

        for($y = 0; $y < $s; $y++){
            for($x = 0; $x < 64; $x++){
                $colorat = @imagecolorat($img, $x, $y);
                $a = ((~((int)($colorat >> 24))) << 1) & 0xff;
                $r = ($colorat >> 16) & 0xff;
                $g = ($colorat >> 8) & 0xff;
                $b = $colorat & 0xff;
                $skinbytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
        return $skinbytes;
    }

    public function getDevilSkin(): Skin{
        return new Skin("DevilSkinDataYa", self::getSkinFromPNG($this->devilSkin));
    }
    public function getGodSkin(): Skin{
        return new Skin("GodSKinDadtaYa", self::getSkinFromPNG($this->godSkin));
    }
    public function getSuperHumanSkin(): Skin{
        return new Skin("SuperHumanSkinDataYa", self::getSkinFromPNG($this->superhumanSkin));
    }




    /**
     * @param Player $player
     * @return bool
     */
    public function closeSession(Player $player): bool{
        if(isset($this->sessions[$player->getId()])){
            unset($this->sessions[$player->getId()]);
            return true;
        }
        return false;
    }

    /**
     * @param int $id
     * @return Session|null
     */
    public function getSessionById(int $id): ?Session{
        if(isset($this->sessions[$id])){
            return $this->sessions[$id];
        }
        return null;
    }

    /**
     * @param string $name
     * @return Session|null
     */
    public function getSessionByName(string $name): ?Session{
        foreach($this->sessions as $session){
            if($session->getPlayer()->getName() === $name){
                return $session;
            }
        }
        return null;
    }

    /**
     * @return null|Entity
     */
    public function getBossEntity(): ?Entity{
        foreach($this->getServer()->getLevels() as $level){
            foreach($level->getEntities() as $entity){
                if($entity instanceof BossEntity){
                    return $entity;
                }
            }
        }
        return null;
    }
}
