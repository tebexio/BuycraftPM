<?php

namespace Buycraft\PocketMine\Execution;

use Buycraft\PocketMine\BuycraftPlugin;
use Buycraft\PocketMine\PluginApi;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

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
            $this->setResult($e);
            return;
        }
    }

    public function onCompletion(Server $server)
    {
        if ($this->getResult() instanceof \Exception) {
            //BuycraftPlugin::getInstance()->getLogger()->logException($this->getResult());
            BuycraftPlugin::getInstance()->getLogger()->error(TextFormat::RED . "Unable to fetch offline commands.");
            return;
        }
        foreach ($this->getResult() as $command) {
            BuycraftPlugin::getInstance()
                ->getCommandExecutionTask()
                ->queue($command, $command->player->name, false, $command->player->uuid ? $command->player->uuid : "");
        }
    }
}