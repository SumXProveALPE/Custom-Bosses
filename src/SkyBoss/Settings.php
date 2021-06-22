<?php

namespace SkyBoss;

use pocketmine\{Server, Player};

final class Settings{

    /** @var string $scoreboardTitle */
    private static $scoreboardTitle;
    /** @var array $scoreboardContent */
    private static $scoreboardContent;
    /** @var float $speed */
    private static $speed;
    /** @var float $scale */
    private static $scale;
    /** @var array $damage */
    private static $damage;
    /** @var array $drops */
    private static $drops;
    /** @var array $spawn */
    private static $spawn;
    /** @var string $skin */
    private static $skin;
    /** @var array $effect */
    private static $effect;
    /** @var float $health */
    private static $health;
    /** @var array $nametag */
    private static $nametag;
    /** @var int $distance */
    private static $distance;
    /** @var float $knockback */
    private static $knockback;

    public static function init(): void{
        $plugin = Main::getInstance();
        $config = $plugin->getConfig();

        self::$scoreboardTitle = $config->get("scoreboardTitle");
        self::$scoreboardContent = $config->get("scoreboardContent");
        self::$speed = $config->get("speed");
        self::$scale = $config->get("scale");
        self::$damage = $config->get("damage");
        self::$drops = $config->get("drops");
        self::$spawn = $config->get("spawn");
        self::$skin = $config->get("skin");
        self::$effect = $config->get("effect");
        self::$health = $config->get("health");
        self::$nametag = $config->get("nametag");
        self::$distance = $config->get("distance");
        self::$knockback = $config->get("knockback");
    }

    /**
     * @return string
     */
    public static function getBossScoreboardTitle(): string{
        return self::$scoreboardTitle;
    }

    /**
     * @return array
     */
    public static function getBossScoreboardContent(): array{
        return self::$scoreboardContent;
    }

    /**
     * @return float
     */
    public static function getBossSpeed(): float{
        return self::$speed;
    }

    /**
     * @return float
     */
    public static function getBossScale(): float{
        return self::$scale;
    }

    /**
     * @return array
     */
    public static function getBossDamage(): array{
        return self::$damage;
    }

    /**
     * @return array
     */
    public static function getBossDrops(): array{
        return self::$drops;
    }

    /**
     * @return array
     */
    public static function getBossSpawn(): array{
        return self::$spawn;
    }

    /**
     * @return string
     */
    public static function getBossSkin(): string{
        return self::$skin;
    }

    /**
     * @return array
     */
    public static function getBossEffect(): array{
        return self::$effect;
    }

    /**
     * @return float
     */
    public static function getBossHealth(): float{
        return self::$health;
    }


    /**
     * @return array
     */
    public static function getBossNametag(): array{
        return self::$nametag;
    }

    /**
     * @return float
     */
    public static function getBossDistance(): float{
        return self::$distance;
    }

    /**
     * @return float
     */
    public static function getBossKnockback(): float{
        return self::$knockback;
    }
}
