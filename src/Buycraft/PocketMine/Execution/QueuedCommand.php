<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 7/18/2016
 * Time: 3:20 AM
 */

namespace Buycraft\PocketMine\Execution;


use pocketmine\Server;

class QueuedCommand
{
    private $command;
    private $username;
    private $queuedTime;
    private $needOnline;

    /**
     * QueuedCommand constructor.
     * @param $command
     */
    public function __construct($command, $username, $needOnline)
    {
        $this->command = $command;
        $this->username = $username;
        $this->queuedTime = time();
        $this->needOnline = $needOnline;
    }

    public function getCommandId()
    {
        return $this->command->id;
    }

    public function canExecute()
    {
        $player = Server::getInstance()->getPlayerExact($this->username);

        if ($this->needOnline)
        {
            if ($player == NULL)
            {
                return false;
            }
        }

        // Check delay.
        if (property_exists($this->command->conditions, "delay"))
        {
            $after = $this->queuedTime + (int) $this->command->conditions->delay;
            if (time() < $after)
            {
                return false;
            }
        }

        // Check inventory slots.
        if (property_exists($this->command->conditions, "slots"))
        {
            // Needing inventory slots implies that the player is online, too.
            if ($player == NULL)
            {
                return false;
            }

            $count = 0;
            for ($i = 0; $i < $player->getInventory()->getSize(); $i++)
            {
                if ($player->getInventory()->getItem($i)->getId() === 0)
                {
                    $count++;
                }
            }

            if ($count < (int) $this->command->conditions->slots)
            {
                return false;
            }
        }

        return true;
    }

    public function getFinalCommand()
    {
        return preg_replace('/[{\\(<\\[](name|player|username)[}\\)>\\]]/i', $this->username, $this->command->command);
    }
}