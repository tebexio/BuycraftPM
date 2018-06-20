<?php

namespace Buycraft\PocketMine\Util;


use Buycraft\PocketMine\BuycraftPlugin;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class RunAsyncTask extends Task
{
    private $asyncTask;

    /**
     * RunAsyncTask constructor.
     * @param $plugin
     * @param $asyncTask
     */
    public function __construct(BuycraftPlugin $plugin, $asyncTask)
    {
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
        Server::getInstance()->getAsyncPool()->submitTask($this->asyncTask);
    }
}
