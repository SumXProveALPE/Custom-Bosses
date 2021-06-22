<?php

namespace SkyBoss\task;

use SkyBoss\Main;
use SkyBoss\Session;
use pocketmine\{
    scheduler\Task, Server, Player
};

class ScoreboardTask extends Task{

    /** @var Main $plugin */
    private $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick){
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
            $session = $this->plugin->getSessionById($player->getId());
            if($session instanceof Session){
                $session->scoreboardContent();
            }
        }
    }
}
