<?php

namespace Buycraft\PocketMine\Execution;


use Buycraft\PocketMine\PluginApi;
use pocketmine\scheduler\AsyncTask;

class DeleteCommandsAsyncTask extends AsyncTask
{
    private $pluginApi;
    private $commands;

    /**
     * DeleteCommandsTask constructor.
     */
    public function __construct(PluginApi $pluginApi, $commands)
    {
        $this->pluginApi = $pluginApi;
        $this->commands = $commands;
    }

    /**
     * Actions to execute when run
     *
     * @param $currentTick
     *
     * @return void
     */
    public function onRun()
    {
        $this->pluginApi->deleteCommands((array)$this->commands);
    }
}