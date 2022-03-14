<?php

namespace Buycraft\PocketMine;

use Buycraft\PocketMine\Commands\BuyCommand;
use Buycraft\PocketMine\Commands\BuycraftCommand;
use Buycraft\PocketMine\Commands\BuycraftCommandAlias;
use Buycraft\PocketMine\Commands\SecretVerificationTask;
use Buycraft\PocketMine\Execution\CommandExecutor;
use Buycraft\PocketMine\Execution\DeleteCommandsTask;
use Buycraft\PocketMine\Execution\DuePlayerCheck;
use Buycraft\PocketMine\Execution\CategoryRefreshTask;
use Buycraft\PocketMine\Util\InventoryUtils;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class BuycraftPlugin extends PluginBase
{
    private static $instance;

    private $pluginApi;
    private $inventoryUtils;

    private $commandExecutionTask;
    private $deleteCommandsTask;
    private $serverInformation;
    private $allDue = array();
    private $categoryRefreshTask = array();
    private $categories = array();

    /**
     * @return BuycraftPlugin
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    public function onEnable():void
    {
        // Ensure cURL is available and supports SSL.
        if (!extension_loaded("curl"))
        {
            $this->getLogger()->error("Tebex-PMMP requires the curl extension to be installed with SSL support. Halting...");
            return;
        }

        $version = curl_version();
        $ssl_supported = ($version['features'] & CURL_VERSION_SSL);
        if (!$ssl_supported)
        {
            $this->getLogger()->error("Tebex-PMMP requires the curl extension to be installed with SSL support. Halting...");
            return;
        }

        self::$instance = $this;

        $this->saveDefaultConfig();

        $secret = $this->getConfig()->get('secret');
        if ($secret) {
            $api = new PluginApi($secret, $this->getDataFolder());
            $this->inventoryUtils = new InventoryUtils($this);
            try {
                $this->verifyInformation($api);
                $this->pluginApi = $api;
                $this->startInitialTasks();
            } catch (\Exception $e) {
                $this->getLogger()->warning("Unable to verify information");
                //$this->getLogger()->logException($e);
            }
        } else {

            //Can we migrate?
            if(file_exists($this->getDataFolder() . "../BuycraftPM/config.yml")){
                $oldconfig = new Config($this->getDataFolder() . "../BuycraftPM/config.yml",Config::YAML);
                if ($oldconfig->get("secret")) {
                    $this->getLogger()->info("Migrating secret from old BuycraftPM plugin...");
                    $this->getServer()->getAsyncPool()->submitTask(
                        new SecretVerificationTask($oldconfig->get("secret"), $this->getDataFolder())
                    );
                }
            } else {

                $this->getLogger()
                    ->info("Looks like this is your first time using Tebex. Set up your server by using 'tebex secret <key>'.");
            }
        }

        $this->getServer()->getPluginManager()->registerEvents(new BuycraftListener($this), $this);
        $this->getServer()->getCommandMap()->register("buycraft", new BuycraftCommandAlias($this, "buycraft"));
        $this->getServer()->getCommandMap()->register("tebex", new BuycraftCommand($this, "tebex"));
        $this->getServer()->getCommandMap()->register("buy", new BuyCommand($this));
    }

    private function verifyInformation(PluginApi $api)
    {
        try {
            $this->serverInformation = $api->basicGet("/information");
        } catch (\Exception $e) {
            $this->getLogger()->warning("Unable to verify information");
            //$this->getLogger()->logException($e);
        }
    }

    private function startInitialTasks()
    {
        $this->commandExecutionTask = new CommandExecutor();
        $this->getScheduler()->scheduleRepeatingTask($this->commandExecutionTask, 1);
        $this->deleteCommandsTask = new DeleteCommandsTask($this->pluginApi);
        $this->getScheduler()->scheduleRepeatingTask($this->deleteCommandsTask, 20);
        $this->categoryRefreshTask = new CategoryRefreshTask($this);
        $this->getScheduler()->scheduleRepeatingTask($this->categoryRefreshTask, 20 * 60 * 3);
        $this->getServer()->getAsyncPool()->submitTask(new DuePlayerCheck($this->pluginApi, true));
    }

    public function onDisable():void
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
     * @return bool
     */
    public function isDue(Player $player): bool
    {
        return isset($this->allDue[$player->getLowerCaseName()]);
    }

    /**
     * @return object
     */
    public function getDue(Player $player)
    {
        return $this->allDue[$player->getLowerCaseName()];
    }

    public function removeDue(Player $player): void
    {
        unset($this->allDue[$player->getLowerCaseName()]);
    }

    /**
     * @param array $allDue
     */
    public function setAllDue(array $allDue)
    {
        $this->allDue = $allDue;
    }

    public function getPlayer(Server $server, $username, $xuid = '')
    {
        if ($xuid != '') {
            $this->getLogger()->info("Checking for existing of player with XUID {$xuid}");
            foreach ($server->getOnlinePlayers() as $player) {
                if ($player->getXuid() === $xuid) {
                    return $player;
                }
            }

            return false;
        }

        $this->getLogger()->info("Checking for existing of player with Username {$username}");

        $player = $server->getPlayerExact($username);

        return $player ? $player : false;
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
        $this->getScheduler()->cancelAllTasks();
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

    public function setCategories(array $categories)
    {
        $this->categories = $categories;
    }

    public function getCategories()
    {
        return $this->categories;
    }

    public function getInventoryUtils()
    {
        return $this->inventoryUtils;
    }
}
