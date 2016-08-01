<?php

namespace Buycraft\PocketMine\Commands;


use Buycraft\PocketMine\BuycraftPlugin;
use Buycraft\PocketMine\Execution\DuePlayerCheck;
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

    private function sendHelp(CommandSender $sender)
    {
        $sender->sendMessage(TextFormat::GREEN . "Usage for the BuycraftMP plugin:");
        $sender->sendMessage(TextFormat::GREEN . "/buycraft secret" . TextFormat::GRAY . ": Set your server's secret.");
        $sender->sendMessage(TextFormat::GREEN . "/buycraft forcecheck" . TextFormat::GRAY . ": Check for current purchases.");
        $sender->sendMessage(TextFormat::GREEN . "/buycraft information" . TextFormat::GRAY . ": Retrieves public information about your web store.");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     *
     * @return mixed
     */
    public function execute(CommandSender $sender, $commandLabel, array $args)
    {
        if (!$sender->hasPermission('buycraft.admin'))
        {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to use Buycraft administrative commands.");
            return true;
        }

        if (count($args) == 0)
        {
            $this->sendHelp($sender);
            return true;
        }

        switch ($args[0])
        {
            case "secret":
                if (count($args) != 2)
                {
                    $sender->sendMessage(TextFormat::RED . "This command requires a secret key.");
                    return true;
                }

                $secret = $args[1];

                // TODO: This is bad, but okay.
                try {
                    $this->plugin->changeSecret($secret);
                    $this->plugin->getConfig()->set('secret', $secret);
                    $sender->sendMessage(TextFormat::GREEN . "Secret set!");
                } catch (\Exception $e) {
                    $this->plugin->getLogger()->logException($e);
                    $sender->sendMessage(TextFormat::RED . "This secret key appears to be invalid. Try again.");
                }
                break;
            case "forcecheck":
                if (count($args) != 1)
                {
                    $sender->sendMessage(TextFormat::RED . "This command doesn't take any arguments.");
                    return true;
                }

                $this->plugin->getServer()->getScheduler()->scheduleAsyncTask(new DuePlayerCheck($this->plugin->getPluginApi(), false));
                $sender->sendMessage(TextFormat::GREEN . "Force check successfully queued.");
                break;
            case "information":
                if (count($args) != 1)
                {
                    $sender->sendMessage(TextFormat::RED . "This command doesn't take any arguments.");
                    return true;
                }

                if ($this->plugin->getServerInformation() == null)
                {
                    $sender->sendMessage(TextFormat::RED . "No server information found (did you forget to set your secret?)");
                    return true;
                }

                $sender->sendMessage(TextFormat::GREEN . "Server " . $this->plugin->getServerInformation()->server->name . " on account " .
                    $this->plugin->getServerInformation()->account->name);
                $sender->sendMessage(TextFormat::GREEN . "Web store URL: " . $this->plugin->getServerInformation()->account->domain);
                $sender->sendMessage(TextFormat::GREEN . "Server currency is " . $this->plugin->getServerInformation()->account->currency->iso_4217);
                break;
        }

        return true;
    }
}