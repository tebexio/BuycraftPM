<?php

namespace Buycraft\PocketMine\Execution;

use Buycraft\PocketMine\BuycraftPlugin;
use Buycraft\PocketMine\PluginApi;
use Buycraft\PocketMine\Util\RunAsyncTask;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class DuePlayerCheck extends AsyncTask
{
    const PLAYERS_PER_PAGE = 250;
    const FALLBACK_DELAY = 300;
    const MAXIMUM_ONLINE_PLAYERS_TO_PROCESS = 60;
    const DELAY_BETWEEN_PLAYERS = 100;

    private $pluginApi;
    private $allowReschedule;

    /**
     * DuePlayerCheck constructor.
     * @param PluginApi $pluginApi
     * @param $allowReschedule boolean
     */
    public function __construct(PluginApi $pluginApi, $allowReschedule)
    {
        $this->pluginApi = $pluginApi;
        $this->allowReschedule = $allowReschedule;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        $page = 1;
        $allDue = array();

        do {
            // Sleep for a while between fetches.
            if ($page > 1) {
                sleep(mt_rand(5, 15) / 10);
            }

            try {
                $result = $this->pluginApi->basicGet("/queue?limit=" . self::PLAYERS_PER_PAGE . "&page=" . $page);
            } catch (\Exception $exception) {
                $this->setResult($exception);

                return;
            }

            if (count($result->players) == 0) {
                break;
            }

            foreach ($result->players as $player) {
                $allDue[strtolower($player->name)] = $player;
            }

            $page++;
        } while ($result->meta->more);

        $this->setResult(array(
            'all_due' => $allDue,
            'next_delay' => $result->meta->next_check ?? self::FALLBACK_DELAY,
            'execute_offline' => $result->meta->execute_offline
        ));
    }

    public function onCompletion(Server $server)
    {
        $plugin = BuycraftPlugin::getInstance();
        $result = $this->getResult();

        // Test if the result is an exception, which indicates something went wrong
        if (!($result instanceof \Exception)) {
            $plugin->getLogger()->info("Found " . count($result['all_due']) . " due player(s).");
            $plugin->setAllDue($result['all_due']);

            // See if we can execute some commands right now
            if ($result['execute_offline']) {
                $plugin->getLogger()->info("Executing commands that can be run now...");
                $server->getScheduler()->scheduleAsyncTask(new ImmediateExecutionRunner($this->pluginApi));
            }

            // Check for player command execution we can do.
            $canProcessNow = array_slice(array_filter($result['all_due'], function ($due) use ($server, $plugin) {
                return $plugin->getPlayer($server, $due->name, $due->uuid ? $due->uuid : "");
            }), 0, self::MAXIMUM_ONLINE_PLAYERS_TO_PROCESS);

            if (count($canProcessNow) > 0) {
                $plugin->getLogger()->info("Running commands for " . count($canProcessNow) . " online player(s)...");

                $at = 1;
                foreach ($canProcessNow as $due) {
                    $this->scheduleDelayedAsyncTask(new PlayerCommandExecutor($this->pluginApi, $due), 10 * $at++);
                }
            }
        } else {
            $plugin->getLogger()->error("Check failed with message: " . $result->getMessage());
        }

        // Reschedule this task if desired.
        if ($this->allowReschedule) {
            // PocketMine-MP doesn't allow us to directly delay the eventual execution of an asynchronous task, so
            // a workaround must be used.
            $nextDelay = $result['next_delay'];
            $this->scheduleDelayedAsyncTask(new DuePlayerCheck($this->pluginApi, true), $nextDelay * 20);
        }
    }

    private function scheduleDelayedAsyncTask($task, $delay)
    {
        Server::getInstance()->getScheduler()->scheduleDelayedTask(new RunAsyncTask(BuycraftPlugin::getInstance(), $task), $delay);
    }
}
