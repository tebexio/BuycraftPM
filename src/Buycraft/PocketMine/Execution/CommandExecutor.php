<?php

namespace Buycraft\PocketMine\Execution;

use Buycraft\PocketMine\BuycraftPlugin;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

class CommandExecutor extends PluginTask
{
    const MAXIMUM_COMMANDS_TO_RUN = 10;

    /**
     * @var array
     */
    private $commands = array();

    /**
     * CommandExecutor constructor.
     */
    public function __construct()
    {
        parent::__construct(BuycraftPlugin::getInstance());
    }

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
        foreach ($this->commands as $command)
        {
            if (count($successfully_executed) >= self::MAXIMUM_COMMANDS_TO_RUN)
            {
                break;
            }

            if ($command->canExecute())
            {
                // TODO: Capture command exceptions for our use.
                if (Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $command->getFinalCommand()))
                {
                    $successfully_executed[] = $command;
                }
            }
        }

        // Now queue all the successfully run commands to be removed from the command queue.
        foreach ($successfully_executed as $executed)
        {
            BuycraftPlugin::getInstance()->getDeleteCommandsTask()->queue($executed->getCommandId());
        }
        $this->commands = array_udiff($this->commands, $successfully_executed, function ($one, $two) {
            return $one->getCommandId() - $two->getCommandId();
        });
    }

    public function queue($command, $username, $online)
    {
        foreach ($this->commands as $inArrayCommand)
        {
            if ($command->id === $inArrayCommand->getCommandId())
            {
                return;
            }
        }

        $this->commands[] = new QueuedCommand($command, $username, $online);
    }
}