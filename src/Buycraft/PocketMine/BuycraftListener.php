<?php

namespace Buycraft\PocketMine;

use Buycraft\PocketMine\Execution\PlayerCommandExecutor;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class BuycraftListener implements Listener
{
    
    /** @var BuycraftPlugin */
    private $plugin;
    
    /**
     * BuycraftListener constructor.
     * @param BuycraftPlugin $plugin
     */
    public function __construct(BuycraftPlugin $plugin)
    {
        $this->plugin = $plugin;
    }
    
    public function onPlayerJoin(PlayerJoinEvent $event)
    {
        $lowerName = strtolower($event->getPlayer()->getName());
        if (array_key_exists($lowerName, $this->plugin->getAllDue())) {
            $duePlayer = $this->plugin->getAllDue()[$lowerName];
            unset($this->plugin->getAllDue()[$lowerName]);

            $this->plugin->getLogger()->info("Executing login commands for " . $event->getPlayer()->getName() . "...");
            $this->plugin->getServer()->getScheduler()->scheduleAsyncTask(new PlayerCommandExecutor($this->plugin->getPluginApi(),
                $duePlayer));
        }
    }
}