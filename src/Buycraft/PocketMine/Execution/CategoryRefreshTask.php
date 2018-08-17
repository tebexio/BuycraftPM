<?php

namespace Buycraft\PocketMine\Execution;


use Buycraft\PocketMine\BuycraftPlugin;
use pocketmine\scheduler\Task;

class CategoryRefreshTask extends Task
{

    private $plugin;

    public function __construct(BuycraftPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick)
    {
        $this->plugin->getLogger()->info("Refreshing category list...");

        $pluginApi = $this->plugin->getPluginApi();

        $request = $pluginApi->basicGet("/listing", true, 10);
        $this->plugin->setCategories($request['categories']);

        $this->plugin->getLogger()->info("Category refresh complete.");
    }
}
