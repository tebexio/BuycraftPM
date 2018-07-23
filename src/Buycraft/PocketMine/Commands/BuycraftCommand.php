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

class BuycraftCommand extends Command
{
    private $plugin;

    /**
     * BuycraftCommand constructor.
     * @param BuycraftPlugin $plugin
     */
    public function __construct(BuycraftPlugin $plugin)
    {
        parent::__construct("buycraft", "Buycraft administrative command.");
        $this->plugin = $plugin;
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     *
     * @return mixed
     */
    public function execute(CommandSender $sender, string$commandLabel, array $args) :bool
    {
        if (!$sender->hasPermission('buycraft.admin')) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to use Buycraft administrative commands.");
            return true;
        }

        if (count($args) == 0) {
            $this->sendHelp($sender);
            return true;
        }

        switch ($args[0]) {
            case "secret":
                if (!($sender instanceof ConsoleCommandSender)) {
                    $sender->sendMessage(TextFormat::RED . "This command must be run from the console.");
                    return true;
                }

                if (count($args) != 2) {
                    $sender->sendMessage(TextFormat::RED . "This command requires a secret key.");
                    return true;
                }

                $secret = $args[1];

                $this->plugin->getServer()->getAsyncPool()->submitTask(new SecretVerificationTask($secret, $this->plugin->getDataFolder()));
                break;
            case "forcecheck":
                if (count($args) != 1) {
                    $sender->sendMessage(TextFormat::RED . "This command doesn't take any arguments.");
                    return true;
                }
                
                if ($this->plugin->getPluginApi() == null) {
                    $sender->sendMessage(TextFormat::RED . "You didn't set your secret (or it is invalid). Please set it and try again.");
                    return true;
                }

                $this->plugin->getServer()->getAsyncPool()->submitTask(new DuePlayerCheck($this->plugin->getPluginApi(), false));
                $sender->sendMessage(TextFormat::GREEN . "Force check successfully queued.");
                break;
            case "info":
                if (count($args) != 1) {
                    $sender->sendMessage(TextFormat::RED . "This command doesn't take any arguments.");
                    return true;
                }

                if ($this->plugin->getServerInformation() == null) {
                    $sender->sendMessage(TextFormat::RED . "No server information found (did you forget to set your secret?)");
                    return true;
                }

                $sender->sendMessage(TextFormat::GREEN . "Server " . $this->plugin->getServerInformation()->server->name . " on account " .
                    $this->plugin->getServerInformation()->account->name);
                if (isset($this->plugin->getServerInformation()->game_type)) {
                    $sender->sendMessage(TextFormat::GREEN . "Web store Type: "
                        . $this->plugin->getServerInformation()->game_type);
                }
                $sender->sendMessage(TextFormat::GREEN . "Web store URL: " . $this->plugin->getServerInformation()->account->domain);
                $sender->sendMessage(TextFormat::GREEN . "Server currency is " . $this->plugin->getServerInformation()->account->currency->iso_4217);
                break;
            case "report":
                if (!($sender instanceof ConsoleCommandSender)) {
                    $sender->sendMessage(TextFormat::RED . "This command must be run from the console.");
                    return true;
                }

                $sender->sendMessage(TextFormat::YELLOW . "Generating report, please wait...");
                $lines = ReportUtil::generateBaseReport();
                $this->plugin->getServer()->getAsyncPool()->submitTask(new FinalizeReportTask($lines));
                break;
        }

        return true;
    }

    private function sendHelp(CommandSender $sender)
    {
        $sender->sendMessage(TextFormat::GREEN . "Usage for the BuycraftPM plugin:");
        $sender->sendMessage(TextFormat::GREEN . "/buycraft secret" . TextFormat::GRAY . ": Set your server's secret.");
        $sender->sendMessage(TextFormat::GREEN . "/buycraft forcecheck" . TextFormat::GRAY . ": Check for current purchases.");
        $sender->sendMessage(TextFormat::GREEN . "/buycraft info" . TextFormat::GRAY . ": Retrieves public information about your web store.");
        $sender->sendMessage(TextFormat::GREEN . "/buycraft report" . TextFormat::GRAY . ": Generates a report you can send to Buycraft support.");
    }
}
