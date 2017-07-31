<?php

namespace Buycraft\PocketMine\Util;


use Buycraft\PocketMine\BuycraftPlugin;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

class RunAsyncTask extends PluginTask
{
    private $asyncTask;

    /**
     * RunAsyncTask constructor.
     * @param $plugin
     * @param $asyncTask
     */
    public function __construct(BuycraftPlugin $plugin, $asyncTask)
    {
        parent::__construct($plugin);
        $this->asyncTask = $asyncTask;
    }

    /**
     * Actions to execute when run
     *
     * @param $currentTick
     *
     * @return void
     */
    public function onRun(int$currentTick)
    {
        Server::getInstance()->getScheduler()->scheduleAsyncTask($this->asyncTask);
    }
}
