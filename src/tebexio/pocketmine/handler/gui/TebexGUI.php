<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\gui;

use pocketmine\Player;
use tebexio\pocketmine\api\listing\TebexListingInfo;
use tebexio\pocketmine\TebexPlugin;

abstract class TebexGUI{

	/** @var TebexPlugin */
	protected $plugin;

	/** @var TebexListingInfo */
	protected $listings;

	final public function __construct(TebexPlugin $plugin, TebexListingInfo $listings){
		$this->plugin = $plugin;
		$this->listings = $listings;
		$this->onInit();
	}

	abstract protected function onInit() : void;

	abstract public function send(Player $player) : void;
}