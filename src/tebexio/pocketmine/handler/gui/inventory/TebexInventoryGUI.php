<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\gui\inventory;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\SharedInvMenu;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use tebexio\pocketmine\api\listing\BaseTebexCategory;
use tebexio\pocketmine\api\utils\sort\Sorter;
use tebexio\pocketmine\handler\gui\TebexGUI;

final class TebexInventoryGUI extends TebexGUI{

	public static function getCategoryItem(BaseTebexCategory $category) : Item{
		return ($category->getGuiItem() ?? ItemFactory::get(ItemIds::PAPER))
			->setCustomName(TextFormat::RESET . TextFormat::WHITE . $category->getName())
			->setLore([TextFormat::RESET . TextFormat::GRAY . "Click to view this category."]);
	}

	/** @var SharedInvMenu */
	private $menu;

	protected function onInit() : void{
		$information = $this->plugin->getInformation();

		$this->menu = InvMenu::create(InvMenu::TYPE_CHEST);
		$this->menu->readonly();
		$this->menu->setName($information->getServer()->getName() . " Webstore");

		$categories = $this->listings->getCategories();
		Sorter::sort($categories);

		$package_items = [];
		$category_inventories = [];
		$slot = 0;

		foreach($categories as $category){
			$package_items[$slot] = self::getCategoryItem($category);
			$category_inventories[$slot] = new TebexCategoryInventory($this->plugin, $category, function(Player $player) : void{ $this->send($player); });
			++$slot;
		}

		$this->menu->getInventory()->setContents($package_items);
		$this->menu->setListener(static function(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) use($category_inventories) : void{
			if(isset($category_inventories[$slot = $action->getSlot()])){
				$category_inventories[$slot]->send($player);
			}
		});
	}

	public function send(Player $player) : void{
		$this->menu->send($player);
	}
}