<?php

namespace Buycraft\PocketMine\Execution;

use Buycraft\PocketMine\BuycraftPlugin;
use Buycraft\PocketMine\PluginApi;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class ImmediateExecutionRunner extends AsyncTask
{
    private $pluginApi;

    /**
     * ImmediateExecutionRunner constructor.
     * @param $pluginApi
     */
    public function __construct(PluginApi $pluginApi)
    {
        $this->pluginApi = $pluginApi;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        try {
            $response = $this->pluginApi->basicGet("/queue/offline-commands");
            $this->setResult($response->commands);
        } catch (\Exception $e) {
            BuycraftPlugin::getInstance()->getLogger()->warning("Unable to fetch offline commands");
            BuycraftPlugin::getInstance()->getLogger()->logException($e);
        }
    }

    public function onCompletion(Server $server)
    {
        foreach ($this->getResult() as $command) {
            BuycraftPlugin::getInstance()
                ->getCommandExecutionTask()
                ->queue($command, $command->player->name, false, $command->player->uuid ? $command->player->uuid : "");
        }
    }
}