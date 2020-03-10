<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\command;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use tebexio\pocketmine\handler\gui\TebexGUI;
use tebexio\pocketmine\handler\TebexGUIHandler;
use tebexio\pocketmine\handler\TebexHandler;

final class TebexBuyCommandExecutor implements CommandExecutor{

	/** @var TebexGUIHandler */
	private $handler;

	public function __construct(TebexHandler $handler){
		$this->handler = $handler->getGuiHandler();
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if($sender instanceof Player){
			$this->handler->getGUI(static function(TebexGUI $gui) use($sender) : void{
				if($sender->isOnline()){
					$gui->send($sender);
				}
			});
		}else{
			$sender->sendMessage(TextFormat::RED . "This command can only be used in-game!");
		}
		return true;
	}
}