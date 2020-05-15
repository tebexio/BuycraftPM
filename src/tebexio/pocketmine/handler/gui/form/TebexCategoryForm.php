<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\gui\form;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\form\Form;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use tebexio\pocketmine\api\listing\BaseTebexCategory;
use tebexio\pocketmine\api\listing\TebexCategory;
use tebexio\pocketmine\api\listing\TebexSubCategory;
use tebexio\pocketmine\api\utils\sort\Sorter;
use tebexio\pocketmine\TebexPlugin;

final class TebexCategoryForm extends SimpleForm{

	/** @var TebexPlugin */
	private $plugin;

	/** @var BaseTebexCategory */
	private $category;

	/** @var Form */
	private $previous_form;

	public function __construct(TebexPlugin $plugin, BaseTebexCategory $category, Form $previous_form){
		parent::__construct(null);
		$this->plugin = $plugin;
		$this->category = $category;
		$this->previous_form = $previous_form;
		$this->onInit();
	}

	protected function onInit() : void{
		$this->setTitle($this->category->getName());
		$this->setContent("Select a package or a subcategory");

		$forms = [];

		if($this->category instanceof TebexCategory){
			$sub_categories = $this->category->getSubcategories();
			Sorter::sort($sub_categories);
			/** @var TebexSubCategory $sub_category */
			foreach($sub_categories as $sub_category){
				$forms[$sub_category_name = $sub_category->getName()] = $sub_category_form = new TebexCategoryForm($this->plugin, $sub_category, $this);
				$this->addButton($sub_category_form->getButtonText(), -1, "", $sub_category_name);
			}
		}

		$packages = $this->category->getPackages();
		Sorter::sort($packages);

		$package_forms = [];

		foreach($packages as $package){
			$package_form = new TebexPackageForm($this->plugin, $package);
			$package_forms[$package_name = $package->getName()] = $package_form;
			$this->addButton($package_form->getButtonText(), SimpleForm::IMAGE_TYPE_URL, $package->getImage() ?? "", $package_name);
		}

		$forms["back"] = $this->previous_form;
		$this->addButton(TextFormat::BOLD . TextFormat::BLACK . "Back" . TextFormat::RESET . TextFormat::EOL . TextFormat::DARK_GRAY . "Back to previous page", -1, "", "back");

		$this->setCallable(function(Player $player, $data) use($forms, $package_forms) : void{
			if(is_string($data)){
				if(isset($forms[$data])){
					$player->sendForm($forms[$data]);
				}elseif(isset($package_forms[$data])){
					$package_forms[$data]->createForm($player, function(SimpleForm $form) use($player) : void{
						if($player->isOnline()){
							$form->addButton(TextFormat::BLACK . TextFormat::BOLD . "Back" . TextFormat::RESET . TextFormat::EOL . TextFormat::DARK_GRAY . "Back to previous page", -1, "", "back");
							$form->setCallable(function(Player $player, $data) : void{
								if($data === "back"){
									$player->sendForm($this);
								}
							});
							$player->sendForm($form);
						}
					});
				}
			}
		});
	}

	public function getButtonText() : string{
		$listings = "";
		if($this->category instanceof TebexCategory){
			$sub_categories_c = count($this->category->getSubcategories());
			if($sub_categories_c > 0){
				$listings .= $sub_categories_c . " subcategor" . ($sub_categories_c === 1 ? "y" : "ies") . ", ";
			}
		}

		$packages_c = count($this->category->getPackages());
		$listings .= $packages_c . " package" . ($packages_c === 1 ? "" : "s");

		return TextFormat::BLACK . TextFormat::BOLD . $this->category->getName() . TextFormat::RESET . TextFormat::EOL .
			TextFormat::DARK_GRAY . $listings;
	}
}