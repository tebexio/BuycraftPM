<?php

namespace Buycraft\PocketMine\Execution;

use Buycraft\PocketMine\BuycraftPlugin;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class CommandExecutor extends Task
{
    const MAXIMUM_COMMANDS_TO_RUN = 10;

    /**
     * @var array
     */
    private $commands = array();

    /**
     * Actions to execute when run
     *
     * @param $currentTick
     *
     * @return void
     */
    public function onRun($currentTick)
    {
        $successfully_executed = array();

        // Run all commands, but only at most MAXIMUM_COMMANDS_TO_RUN commands.
        foreach ($this->commands as $id => $command) {
            if (count($successfully_executed) >= self::MAXIMUM_COMMANDS_TO_RUN) {
                break;
            }

            if ($command->canExecute()) {
                // TODO: Capture command exceptions for our use.
                if (Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $command->getFinalCommand())) {
                    $successfully_executed[] = $command;
                }
            }
        }

        // Now queue all the successfully run commands to be removed from the command queue.
        foreach ($successfully_executed as $executed) {
            BuycraftPlugin::getInstance()->getDeleteCommandsTask()->queue($executed->getCommandId());
			unset($this->commands[$executed->getCommandId()]);
        }
    }

    public function queue($command, $username, $online)
    {
        $this->commands[$command->id] = new QueuedCommand($command, $username, $online);
    }
}
