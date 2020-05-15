<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\command;

use tebexio\pocketmine\TebexPlugin;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use tebexio\pocketmine\thread\TebexException;

class UnregisteredTebexCommandExecutor implements CommandExecutor{

	/** @var TebexPlugin */
	protected $plugin;

	public function __construct(TebexPlugin $plugin){
		$this->plugin = $plugin;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(isset($args[0], $args[1]) && $args[0] === "secret"){
			$this->onTypeSecret($sender, $command, $label, $args[1]);
			return true;
		}

		$sender->sendMessage("Usage: /" . $label . " secret <secret>");
		return false;
	}

	protected function onTypeSecret(CommandSender $sender, Command $command, string $label, string $secret) : void{
		$info = null;

		try{
			$info = $this->plugin->setSecret($secret);
		}catch(TebexException $e){
			$sender->sendMessage($e->getMessage());
		}

		if($info !== null){
			$config = $this->plugin->getConfig();
			$config->set("secret", $secret);
			$config->save();

			$account = $info->getAccount();
			$server = $info->getServer();
			$sender->sendMessage("Successfully logged in to server (#" . $server->getId() . ") " . $server->getName() . " as (#" . $account->getId() . ") " . $account->getName() . "!");
		}
	}
}