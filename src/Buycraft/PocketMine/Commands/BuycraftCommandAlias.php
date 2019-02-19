<?php

namespace Buycraft\PocketMine\Commands;


use Buycraft\PocketMine\BuycraftPlugin;
use Buycraft\PocketMine\Execution\DuePlayerCheck;
use Buycraft\PocketMine\Util\FinalizeReportTask;
use Buycraft\PocketMine\Util\ReportUtil;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\Server;

class BuycraftCommandAlias extends Command
{
    private $plugin;

    /**
     * BuycraftCommand constructor.
     * @param BuycraftPlugin $plugin
     */
    public function __construct(BuycraftPlugin $plugin, string $cmd)
    {
        parent::__construct($cmd, "Buycraft administrative command.");
        $this->plugin = $plugin;
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     *
     * @return mixed
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) :bool
    {
        Server::getInstance()->dispatchCommand($sender, "tebex " . implode(" ", $args));
        return true;
    }

    private function sendHelp(CommandSender $sender)
    {
        $sender->sendMessage(TextFormat::GREEN . "Usage for the Tebex-PMMP plugin:");
        $sender->sendMessage(TextFormat::GREEN . "/tebex:secret" . TextFormat::GRAY . ": Set your server's secret.");
        $sender->sendMessage(TextFormat::GREEN . "/tebex:forcecheck" . TextFormat::GRAY . ": Check for current purchases.");
        $sender->sendMessage(TextFormat::GREEN . "/tebex:info" . TextFormat::GRAY . ": Retrieves public information about your web store.");
        $sender->sendMessage(TextFormat::GREEN . "/tebex:report" . TextFormat::GRAY . ": Generates a report you can send to Buycraft support.");
    }
}
