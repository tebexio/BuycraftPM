<?php

namespace Buycraft\PocketMine\Commands;


use Buycraft\PocketMine\BuycraftPlugin;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class BuyCommand extends Command
{
    private $plugin;

    public function __construct(BuycraftPlugin $plugin)
    {
        parent::__construct("buy", "Browse purchasable packages in-game.");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof Player) {
            $this->plugin->getInventoryUtils()->showCategoryGui($sender);
        } else {
            $this->plugin->getLogger()->error("Only in-game players can execute the /buy command");
        }
        return true;
    }
}
