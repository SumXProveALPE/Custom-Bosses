<?php

namespace SkyBoss\task;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class SpawnBossTask extends Task{

    private $countdown = 60 * 60 * 30;

    public function onRun(int $currentTick): void{
        $this->countdown--;
        if($this->countdown <= 0){
            Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "boss start");
            $this->getHandler()->cancel();
        }

    }
}