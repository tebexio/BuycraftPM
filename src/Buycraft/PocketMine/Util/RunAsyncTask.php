<?php
/**
 * Created by PhpStorm.
 * User: andrew
 * Date: 7/16/16
 * Time: 9:30 AM
 */

namespace Buycraft\PocketMine\Util;


use pocketmine\scheduler\PluginTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class RunAsyncTask extends PluginTask
{
    private $asyncTask;

    /**
     * RunAsyncTask constructor.
     * @param $asyncTask
     */
    public function __construct($plugin, $asyncTask)
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
    public function onRun($currentTick)
    {
        Server::getInstance()->getScheduler()->scheduleAsyncTask($this->asyncTask);
    }
}