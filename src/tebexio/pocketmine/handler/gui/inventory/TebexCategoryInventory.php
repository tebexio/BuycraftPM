<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\gui\inventory;

use Closure;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\SharedInvMenu;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use tebexio\pocketmine\api\checkout\TebexCheckoutInfo;
use tebexio\pocketmine\api\listing\BaseTebexCategory;
use tebexio\pocketmine\api\listing\TebexCategory;
use tebexio\pocketmine\api\utils\sort\Sorter;
use tebexio\pocketmine\TebexPlugin;
use tebexio\pocketmine\thread\response\TebexResponseHandler;

final class TebexCategoryInventory{

	/** @var TebexPlugin */
	private $plugin;

	/** @var BaseTebexCategory */
	private $category;

	/** @var SharedInvMenu */
	private $menu;

	/**
	 * @var Closure
	 * @phpstan-var Closure(Player) : void
	 */
	private $send_back;

	/**
	 * @param TebexPlugin $plugin
	 * @param BaseTebexCategory $category
	 * @param Closure $send_back
	 *
	 * @phpstan-param Closure(Player) : void $send_back
	 */
	public function __construct(TebexPlugin $plugin, BaseTebexCategory $category, Closure $send_back){
		$this->plugin = $plugin;
		$this->category = $category;
		$this->send_back = $send_back;
		$this->onInit();
	}

	protected function onInit() : void{
		$this->menu = InvMenu::create(InvMenu::TYPE_CHEST);
		$this->menu->readonly();
		$this->menu->setName($this->category->getName());

		$packages = $this->category->getPackages();
		Sorter::sort($packages);

		$contents = [];
		$sub_category_inventories = [];
		$inventory_packages = [];
		$slot = 0;

		if($this->category instanceof TebexCategory){
			$sub_categories = $this->category->getSubcategories();
			Sorter::sort($sub_categories);
			foreach($sub_categories as $sub_category){
				$sub_category_inventories[$slot] = new TebexCategoryInventory($this->plugin, $sub_category, function(Player $player) : void{ $this->send($player); });
				$contents[$slot] = TebexInventoryGUI::getCategoryItem($sub_category);
				++$slot;
			}
		}

		$currency = $this->plugin->getInformation()->getAccount()->getCurrency()->getSymbol();
		foreach($packages as $package){
			$item = $package->getGuiItem() ?? ItemFactory::get(ItemIds::PAPER);
			$item->setCustomName(TextFormat::RESET . $package->getName());
			$item->setLore([
				TextFormat::RESET . TextFormat::WHITE . "Price: " . TextFormat::GRAY . $currency . $package->getSale()->getPostDiscountPrice($package->getPrice())
			]);

			$inventory_packages[$slot] = $package;
			$contents[$slot] = $item;

			++$slot;
		}

		$back_slot = $this->menu->getInventory()->getSize() - 1;
		$contents[$back_slot] = ItemFactory::get(ItemIds::ARROW)
			->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Back")
			->setLore([TextFormat::RESET . TextFormat::GRAY . "Back to previous menu"]);

		$this->menu->getInventory()->setContents($contents);
		$this->menu->setListener(function(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) use($inventory_packages, $sub_category_inventories, $back_slot) : void{
			if(isset($inventory_packages[$slot = $action->getSlot()])){
				$player->removeWindow($action->getInventory());
				$package = $inventory_packages[$slot];
				$this->plugin->getApi()->checkout($inventory_packages[$slot]->getId(), $player->getName(), TebexResponseHandler::onSuccess(static function(TebexCheckoutInfo $info) use($player, $package) : void{
					if($player->isOnline()){
						$player->sendMessage(TextFormat::WHITE . "Visit the webstore at " . TextFormat::GRAY . $info->getUrl() . TextFormat::WHITE . " to purchase " . $package->getName(). ".");
					}
				}));
			}elseif(isset($sub_category_inventories[$slot])){
				$sub_category_inventories[$slot]->send($player);
			}elseif($slot === $back_slot){
				($this->send_back)($player);
			}
		});
	}

	public function send(Player $player) : void{
		$this->menu->send($player);
	}
}