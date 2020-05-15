<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\gui\form;

use Closure;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use tebexio\pocketmine\api\checkout\TebexCheckoutInfo;
use tebexio\pocketmine\api\listing\TebexPackage;
use tebexio\pocketmine\TebexPlugin;
use tebexio\pocketmine\thread\response\TebexResponseHandler;

final class TebexPackageForm{

	/** @var TebexPlugin */
	private $plugin;

	/** @var TebexPackage */
	private $package;

	public function __construct(TebexPlugin $plugin, TebexPackage $package){
		$this->plugin = $plugin;
		$this->package = $package;
	}

	public function getButtonText() : string{
		$currency = $this->plugin->getInformation()->getAccount()->getCurrency();

		$sale_info = "";
		$sale = $this->package->getSale();
		if($sale->isActive()){
			$sale_info = TextFormat::DARK_RED . " [ON SALE -" . $currency->getSymbol() . $sale->getDiscount() . "]";
		}

		return TextFormat::BLACK . TextFormat::BOLD . $this->package->getName() . TextFormat::RESET . TextFormat::EOL .
			TextFormat::DARK_GRAY . $sale->getPostDiscountPrice($this->package->getPrice()) . " " . $currency->getIso4217() . $sale_info;
	}

	/**
	 * @param Player $player
	 * @param Closure $callback
	 *
	 * @phpstan-param Closure(SimpleForm $form) : void $callback
	 */
	public function createForm(Player $player, Closure $callback) : void{
		$this->plugin->getApi()->checkout($this->package->getId(), $player->getName(), TebexResponseHandler::onSuccess(function(TebexCheckoutInfo $info) use($callback) : void{
			$information = $this->plugin->getInformation();

			$currency = $information->getAccount()->getCurrency();

			$body = "";
			$body .= TextFormat::WHITE . "Price: " . TextFormat::GRAY . $currency->getSymbol() . $this->package->getSale()->getPostDiscountPrice($this->package->getPrice()) . TextFormat::EOL;

			$body .= TextFormat::EOL;

			$sale = $this->package->getSale();
			if($sale->isActive()){
				$body .= TextFormat::WHITE . "This package is currently on " . TextFormat::BOLD . "SALE" . TextFormat::RESET . TextFormat::WHITE . " for " . TextFormat::DARK_RED . "-" . $currency->getSymbol() . $sale->getDiscount() . TextFormat::WHITE . "!" . TextFormat::EOL;
				$body .= TextFormat::EOL;
			}

			$body .= TextFormat::WHITE . "Visit the webstore at " . TextFormat::GRAY . $info->getUrl() . TextFormat::WHITE . " to purchase this package." . TextFormat::EOL;

			$form = new SimpleForm(null);
			$form->setTitle($this->package->getName());
			$form->setContent($body);
			$callback($form);
		}));
	}
}