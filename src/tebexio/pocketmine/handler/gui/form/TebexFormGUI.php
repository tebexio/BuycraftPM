<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\gui\form;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\form\Form;
use pocketmine\Player;
use tebexio\pocketmine\api\utils\sort\Sorter;
use tebexio\pocketmine\handler\gui\TebexGUI;

final class TebexFormGUI extends TebexGUI{

	/** @var Form */
	private $form;

	protected function onInit() : void{
		$this->form = new SimpleForm(null);
		$this->form->setTitle( $this->plugin->getInformation()->getServer()->getName() . " Webstore");
		$this->form->setContent("Select a category");

		$category_forms = [];
		$categories = $this->listings->getCategories();
		Sorter::sort($categories);
		foreach($categories as $category){
			$category_form = new TebexCategoryForm($this->plugin, $category, $this->form);
			$category_forms[$category_name = $category->getName()] = $category_form;
			$this->form->addButton($category_form->getButtonText(), -1, "", $category_name);
		}

		$this->form->setCallable(static function(Player $player, $data) use($category_forms) : void{
			if(is_string($data) && isset($category_forms[$data])){
				$player->sendForm($category_forms[$data]);
			}
		});
	}

	public function send(Player $player) : void{
		$player->sendForm($this->form);
	}
}