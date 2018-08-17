<?php

namespace Buycraft\PocketMine;

use Buycraft\PocketMine\Execution\PlayerCommandExecutor;
use Buycraft\PocketMine\Util\CategoryInventory;
use Buycraft\PocketMine\Util\PackageInventory;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\SetEntityLinkPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class BuycraftListener implements Listener
{

    private $plugin;

    public function __construct(BuycraftPlugin $main)
    {
        $this->plugin = $main;
    }

    public function onPlayerJoin(PlayerJoinEvent $event)
    {
        BuycraftPlugin::getInstance()->getLogger()->info("Executing login commands for " . $event->getPlayer()->getName() . "[XUID: {$event->getPlayer()->getXuid()}]...");

        $lowerName = strtolower($event->getPlayer()->getName());
        if (array_key_exists($lowerName, BuycraftPlugin::getInstance()->getAllDue())) {
            $duePlayer = BuycraftPlugin::getInstance()->getAllDue()[$lowerName];
            unset(BuycraftPlugin::getInstance()->getAllDue()[$lowerName]);

            BuycraftPlugin::getInstance()->getLogger()->info("Executing login commands for " . $event->getPlayer()->getName() . "[XUID: {$event->getPlayer()->getXuid()}]...");
            Server::getInstance()->getAsyncPool()->submitTask(new PlayerCommandExecutor(BuycraftPlugin::getInstance()->getPluginApi(),
                $duePlayer));
        }
    }


    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $tr = $event->getTransaction();
        $actions = $tr->getActions();


        foreach ($actions as $action) {
            if ($action instanceof SlotChangeAction) {
                if ($action->getInventory() instanceof CategoryInventory ||
                    $action->getInventory() instanceof PackageInventory) {
                    $event->setCancelled(true);
                }
            }
        }


        if (count($actions) == 2) {
            reset($actions);
            $action = $tr->getActions()[key($actions)];

            if ($action instanceof SlotChangeAction) {
                $target = $action->getTargetItem();
                if ($target->getNamedTag()->hasTag("buycraft-continue", IntTag::class)) {
                    if ($action->getInventory() instanceof CategoryInventory) {
                        $event->setCancelled();
                        $this->handleCategoryInventoryClick($event, $action);
                    } elseif
                    ($action->getInventory() instanceof PackageInventory) {
                        $event->setCancelled();
                        $this->handlePackageInventoryClick($event, $action);
                    }
                }
            }
        }

    }


    private function handleCategoryInventoryClick(InventoryTransactionEvent $event, SlotChangeAction $action)
    {
        $item = $action->getSourceItem();

        if (!$item) {
            return false;
        }

        $nbt = $item->getNamedTag();

        if (!$nbt->hasTag("categoryId", IntTag::class)) {
            return false;
        }

        $p = $event->getTransaction()->getSource();

        $p->removeWindow($action->getInventory());

        $inventoryUtils = $this->plugin->getInventoryUtils();

        $this->plugin->getScheduler()->scheduleDelayedTask(new class($inventoryUtils, $p, $nbt) extends Task
        {

            private $inventoryUtils;
            private $p;
            private $nbt;

            public function __construct($inventoryUtils, $p, $nbt)
            {
                $this->inventoryUtils = $inventoryUtils;
                $this->p = $p;
                $this->nbt = $nbt;
            }

            function onRun(int $currentTick)
            {
                $this->inventoryUtils->showPackageGui($this->p, $this->nbt->getInt("categoryId"));
            }

        }, 10);

        return true;
    }

    private
    function handlePackageInventoryClick(InventoryTransactionEvent $event, SlotChangeAction $action)
    {
        $item = $action->getSourceItem();

        if (!$item) {
            return false;
        }


        $nbt = $item->getNamedTag();

        if (!$nbt->hasTag("packageId", IntTag::class)) {
            return false;
        }

        $p = $event->getTransaction()->getSource();
        $packageId = $nbt->getInt("packageId");

        $p->removeWindow($action->getInventory());

        $url = $this->plugin->getInventoryUtils()->getPackageLink($p, $packageId);

        $pk = new TextPacket();
        $pk->type = TextPacket::TYPE_RAW;
        $pk->message = "§a" . $url;

        $p->sendDataPacket($pk, false, false);


        return true;
    }
}