<?php

namespace Buycraft\PocketMine\Execution;


use Buycraft\PocketMine\BuycraftPlugin;
use Buycraft\PocketMine\PluginApi;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class PlayerCommandExecutor extends AsyncTask
{
    private $pluginApi;
    private $due;

    /**
     * PlayerCommandExecutor constructor.
     * @param PluginApi $pluginApi
     * @param $due
     */
    public function __construct(PluginApi $pluginApi, $due)
    {
        $this->pluginApi = $pluginApi;
        $this->due = $due;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        $this->setResult($this->pluginApi->basicGet('/queue/online-commands/' . $this->due->id)->commands);
    }

    public function onCompletion(Server $server)
    {
        foreach ($this->getResult() as $command)
        {
            BuycraftPlugin::getInstance()->getCommandExecutionTask()->queue($command, $this->due->name, true);
        }
    }
}