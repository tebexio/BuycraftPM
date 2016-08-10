<?php

namespace Buycraft\PocketMine;

use Buycraft\PocketMine\Execution\PlayerCommandExecutor;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Server;

class BuycraftListener implements Listener
{
    public function onPlayerJoin(PlayerJoinEvent $event)
    {
        $lowerName = strtolower($event->getPlayer()->getName());
        if (array_key_exists($lowerName, BuycraftPlugin::getInstance()->getAllDue())) {
            $duePlayer = BuycraftPlugin::getInstance()->getAllDue()[$lowerName];
            unset(BuycraftPlugin::getInstance()->getAllDue()[$lowerName]);

            BuycraftPlugin::getInstance()->getLogger()->info("Executing login commands for " . $event->getPlayer()->getName() . "...");
            Server::getInstance()->getScheduler()->scheduleAsyncTask(new PlayerCommandExecutor(BuycraftPlugin::getInstance()->getPluginApi(),
                $duePlayer));
        }
    }
}