<?php

namespace Buycraft\PocketMine\Execution;


use Buycraft\PocketMine\BuycraftPlugin;
use Buycraft\PocketMine\PluginApi;
use pocketmine\scheduler\PluginTask;

class DeleteCommandsTask extends PluginTask
{
    const MAXIMUM_COMMANDS_TO_POST = 100;

    private $commandIds = array();
    private $pluginApi;

    /**
     * DeleteCommandsTask constructor.
     * @param $plugin
     */
    public function __construct(PluginApi $pluginApi)
    {
        parent::__construct(BuycraftPlugin::getInstance());
        $this->pluginApi = $pluginApi;
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
        if (count($this->commandIds) > self::MAXIMUM_COMMANDS_TO_POST) {
            // Only consider the first MAXIMUM_COMMANDS_TO_POST commands.
            $toPost = array_slice($this->commandIds, 0, self::MAXIMUM_COMMANDS_TO_POST);
            $this->commandIds = array_diff($this->commandIds, $toPost);
        } else {
            // Copy the array
            $toPost = $this->commandIds;
            $this->commandIds = array();
        }

        if (isset($toPost) && count($toPost) > 0) {
            BuycraftPlugin::getInstance()->getServer()->getScheduler()->scheduleAsyncTask(new DeleteCommandsAsyncTask($this->pluginApi, $toPost));
        }
    }

    /**
     * Immediately purges all queued commands.
     */
    public function sendAllCommands()
    {
        if (count($this->commandIds) > self::MAXIMUM_COMMANDS_TO_POST) {
            $chunked = array_chunk($this->commandIds, self::MAXIMUM_COMMANDS_TO_POST);
            foreach ($chunked as $chunk) {
                BuycraftPlugin::getInstance()->getPluginApi()->deleteCommands($chunk);
            }
        } else {
            BuycraftPlugin::getInstance()->getPluginApi()->deleteCommands($this->commandIds);
        }
    }

    /**
     * Queues a command to be marked complete.
     * @param $id integer
     */
    public function queue($id)
    {
        if (!in_array($id, $this->commandIds)) {
            $this->commandIds[] = $id;
        }
    }
}