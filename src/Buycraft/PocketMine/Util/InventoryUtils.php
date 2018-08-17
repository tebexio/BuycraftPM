<?php

namespace Buycraft\PocketMine\Util;

use Buycraft\PocketMine\BuycraftPlugin;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;

class InventoryUtils
{

    private $plugin;

    public function __construct(BuycraftPlugin $main)
    {
        $this->plugin = $main;
    }

    public function showCategoryGui(Player $p)
    {
        $inv = new CategoryInventory();

        $i = 0;

        foreach ($this->plugin->getCategories() as $category) {
            if ($i > 25) {
                break;
            }
            $item = Item::fromString($category['gui_item'] ?? "CHEST");

            $nbt = $item->getNamedTag() ?? new CompoundTag("", []);
            $nbt->setTag(new IntTag("categoryId", $category['id']));
            $item->setNamedTag($nbt);

            $item->setCustomName("§r§f" . $category['name']);

            $inv->addItem($item);

            $i++;
        }

        $item = Item::get(Item::WOOL, 5);
        $nbt = $item->getNamedTag() ?? new CompoundTag("", []);
        $nbt->setTag(new IntTag("buycraft-continue", 1));
        $item->setNamedTag($nbt);
        $item->setCustomName("§r§aDrag an item here");
        $inv->setItem(26, $item);

        $p->addWindow($inv);
    }

    public function showPackageGui(Player $p, $categoryId)
    {
        $category = false;

        foreach ($this->plugin->getCategories() as $loopedCategory) {
            if ($loopedCategory['id'] === $categoryId) {
                $category = $loopedCategory;
            }
        }

        if (!$category) {
            $p->sendMessage("There was a problem loading the packages for this category");
            return false;
        }

        $inv = new PackageInventory();
        $currency = $this->plugin->getServerInformation()->account->currency->symbol;

        $i = 0;
        foreach ($category['packages'] as $package) {
            if ($i > 25) {
                break;
            }

            $item = Item::fromString($package['gui_item'] ?? "PAPER");

            $nbt = $item->getNamedTag() ?? new CompoundTag("", []);
            $nbt->setTag(new IntTag("packageId", $package['id']));
            $item->setNamedTag($nbt);

            $item->setCustomName("§r§f" . $package['name']);

            $item->setLore([
                "§r§7Price: " . $currency . $package['price']
            ]);

            $inv->addItem($item);
            $i++;
        }

        $item = Item::get(Item::WOOL, 5);
        $nbt = $item->getNamedTag() ?? new CompoundTag("", []);
        $nbt->setTag(new IntTag("buycraft-continue", 1));
        $item->setNamedTag($nbt);
        $item->setCustomName("§r§aDrag an item here");
        $inv->setItem(26, $item);

        $p->addWindow($inv);
    }

    public function getPackageLink(Player $player, $packageId)
    {
        $request = $this->plugin->getPluginApi()->post("/checkout", [
            "username" => $player->getName(),
            "package_id" => $packageId
        ]);

        return $request['url'];
    }

}