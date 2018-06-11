<?php

namespace Buycraft\PocketMine;

use Buycraft\PocketMine\Commands\BuycraftCommand;
use Buycraft\PocketMine\Execution\CommandExecutor;
use Buycraft\PocketMine\Execution\DeleteCommandsTask;
use Buycraft\PocketMine\Execution\DuePlayerCheck;
use Buycraft\PocketMine\Util\AnalyticsSend;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class BuycraftPlugin extends PluginBase
{
    private static $instance;
    private $pluginApi;
    private $commandExecutionTask;
    private $deleteCommandsTask;
    private $serverInformation;
    private $allDue = array();

    /**
     * @return BuycraftPlugin
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    public function onEnable()
    {
        // Ensure cURL is available and supports SSL.
        if (!extension_loaded("curl"))
        {
            $this->getLogger()->error("BuycraftPM requires the curl extension to be installed with SSL support. Halting...");
            return;
        }

        $version = curl_version();
        $ssl_supported = ($version['features'] & CURL_VERSION_SSL);
        if (!$ssl_supported)
        {
            $this->getLogger()->error("BuycraftPM requires the curl extension to be installed with SSL support. Halting...");
            return;
        }

        self::$instance = $this;

        $this->saveDefaultConfig();

        $secret = $this->getConfig()->get('secret');
        if ($secret) {
            $api = new PluginApi($secret, $this->getDataFolder());
            try {
                $this->verifyInformation($api);
                $this->pluginApi = $api;
                $this->startInitialTasks();
            } catch (\Exception $e) {
                $this->getLogger()->warning("Unable to verify information");
                $this->getLogger()->logException($e);
            }
        } else {
            $this->getLogger()->info("Looks like this is your first time using Buycraft. Set up your server by using 'buycraft secret <key>'.");
        }

        $this->getServer()->getPluginManager()->registerEvents(new BuycraftListener(), $this);
        $this->getServer()->getCommandMap()->register("buycraft", new BuycraftCommand($this));
    }

    private function verifyInformation(PluginApi $api)
    {
        $this->serverInformation = $api->basicGet("/information");

    }

    private function startInitialTasks()
    {
        $this->commandExecutionTask = new CommandExecutor($this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask($this->commandExecutionTask, 1);
        $this->deleteCommandsTask = new DeleteCommandsTask($this->pluginApi);
        $this->getServer()->getScheduler()->scheduleRepeatingTask($this->deleteCommandsTask, 20);
        $this->getServer()->getScheduler()->scheduleAsyncTask(new DuePlayerCheck($this->pluginApi, true));

        AnalyticsSend::sendAnalytics($this);
    }

    public function onDisable()
    {
        $this->saveConfig();
    }

    /**
     * @return PluginApi
     */
    public function getPluginApi()
    {
        return $this->pluginApi;
    }

    /**
     * @return CommandExecutor
     */
    public function getCommandExecutionTask()
    {
        return $this->commandExecutionTask;
    }

    /**
     * @return DeleteCommandsTask
     */
    public function getDeleteCommandsTask()
    {
        return $this->deleteCommandsTask;
    }

    /**
     * @return array
     */
    public function getAllDue(): array
    {
        return $this->allDue;
    }

    public function getPlayer(Server $server, $username, $xuid = '')
    {
        if ($xuid != '') {
            if($this->getConfig()->get('debug') == "true") {
                $this->getLogger()->info("Checking for existing of player with XUID {$xuid}");
            }

            foreach ($server->getOnlinePlayers() as $player) {
                if ($player->getXuid() === $xuid) {
                    return $player;
                }
            }

            return false;
        }
        if($this->getConfig()->get('debug') == "true") {
            $this->getLogger()->info("Checking for existing of player with Username {$username}");
        }

        $player = $server->getPlayerExact($username);

        return $player ? $player : false;
    }

    /**
     * @param array $allDue
     */
    public function setAllDue(array $allDue)
    {
        // Because PHP logic.
        $this->allDue = (array)$allDue;
    }

    /**
     * Attempts to change the current API object. Will not always work, but due to the "design" of threaded PHP, this
     * is the only way we can accomplish this.
     * @param $newApi PluginApi
     * @param $information mixed
     */
    public function changeApi(PluginApi $newApi, $information)
    {
        $this->pluginApi = $newApi;
        $this->getServer()->getScheduler()->cancelTasks($this);
        $this->startInitialTasks();

        // change information if required (for secret command)
        if ($information !== NULL) {
            $this->serverInformation = $information;
        }
    }

    /**
     * @return mixed
     */
    public function getServerInformation()
    {
        return $this->serverInformation;
    }
}