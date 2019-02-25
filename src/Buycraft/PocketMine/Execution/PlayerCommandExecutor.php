<?php

namespace Buycraft\PocketMine\Execution;


use Buycraft\PocketMine\BuycraftPlugin;
use Buycraft\PocketMine\PluginApi;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

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
        try {
            $this->setResult($this->pluginApi->basicGet('/queue/online-commands/' . $this->due->id)->commands);
        } catch (\Exception $e) {
            $this->setResult($e);
            return;
        }
    }

    public function onCompletion(Server $server)
    {
        if ($this->getResult() instanceof \Exception) {
            //BuycraftPlugin::getInstance()->getLogger()->logException($this->getResult());
            BuycraftPlugin::getInstance()->getLogger()->error(TextFormat::RED . "Unable to fetch online commands for player.");
            return;
        }
        foreach ($this->getResult() as $command) {
            BuycraftPlugin::getInstance()
                ->getCommandExecutionTask()
                ->queue($command, $this->due->name, true, $this->due->uuid ? $this->due->uuid : "");
        }
    }
}