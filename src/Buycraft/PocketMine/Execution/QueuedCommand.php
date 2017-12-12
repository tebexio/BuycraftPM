<?php

namespace Buycraft\PocketMine\Execution;


use Buycraft\PocketMine\BuycraftPlugin;
use pocketmine\Server;

class QueuedCommand
{
    private $command;
    private $username;
    private $queuedTime;
    private $needOnline;

    /**
     * QueuedCommand constructor.
     * @param $command mixed
     * @param $username string
     * @param $needOnline boolean
     */
    public function __construct($command, $username, $needOnline, $xuid = '')
    {
        $this->command = $command;
        $this->username = $username;
        $this->xuid = $xuid;
        $this->queuedTime = time();
        $this->needOnline = $needOnline;
    }

    public function getCommandId()
    {
        return $this->command->id;
    }



    public function canExecute()
    {
        $plugin = BuycraftPlugin::getInstance();
        $player = $plugin->getPlayer(Server::getInstance(), $this->username, $this->xuid);

        if ($this->needOnline) {
            if (!$player) {
                return false;
            }
        }

        // Check delay.
        if (property_exists($this->command->conditions, "delay")) {
            $after = $this->queuedTime + (int)$this->command->conditions->delay;
            if (time() < $after) {
                return false;
            }
        }

        // Check inventory slots.
        if (property_exists($this->command->conditions, "slots")) {
            // Needing inventory slots implies that the player is online, too.
            if ($player == NULL) {
                return false;
            }

            $count = 0;
            for ($i = 0; $i < $player->getInventory()->getSize(); $i++) {
                if ($player->getInventory()->getItem($i)->getId() === 0) {
                    $count++;
                }
            }

            if ($count < (int)$this->command->conditions->slots) {
                return false;
            }
        }

        return true;
    }

    public function getFinalCommand()
    {
        $command = str_replace(
            [
                '{name}',
                '{player}',
                '{username}',
                '{uuid}',
                '{xuid}',
                '{id}'
            ],
            [
                $this->username,
                $this->username,
                $this->username,
                $this->xuid,
                $this->xuid,
                $this->xuid,
            ],
            $this->command->command
        );

        return $command;
    }
}