<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\command;

use tebexio\pocketmine\handler\TebexHandler;
use tebexio\pocketmine\TebexPlugin;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

final class TebexCommandExecutor extends UnregisteredTebexCommandExecutor{

	/** @var TebexHandler */
	private $handler;

	public function __construct(TebexPlugin $plugin, TebexHandler $handler){
		parent::__construct($plugin);
		$this->handler = $handler;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(isset($args[0])){
			switch($args[0]){
				case "info":
					$info = $this->plugin->getInformation();
					$account = $info->getAccount();
					$server = $info->getServer();

					$sender->sendMessage(
						"" . TextFormat::EOL .
						TextFormat::BOLD . TextFormat::WHITE . "Tebex Account" . TextFormat::RESET . TextFormat::EOL .
						TextFormat::WHITE . "ID: " . TextFormat::GRAY . $account->getId() . TextFormat::EOL .
						TextFormat::WHITE . "Domain: " . TextFormat::GRAY . $account->getDomain() . TextFormat::EOL .
						TextFormat::WHITE . "Name: " . TextFormat::GRAY . $account->getName() . TextFormat::EOL .
						TextFormat::WHITE . "Currency: " . TextFormat::GRAY . $account->getCurrency()->getIso4217() . " (" . $account->getCurrency()->getSymbol() . ")" . TextFormat::EOL .
						TextFormat::WHITE . "Online Mode: " . TextFormat::GRAY . ($account->isOnlineModeEnabled() ? "Enabled" : "Disabled") . TextFormat::EOL .
						TextFormat::WHITE . "Game Type: " . TextFormat::GRAY . $account->getGameType() . TextFormat::EOL .
						TextFormat::WHITE . "Event Logging: " . TextFormat::GRAY . ($account->isLogEventsEnabled() ? "Enabled" : "Disabled") . TextFormat::EOL .
						"" . TextFormat::EOL .
						TextFormat::BOLD . TextFormat::WHITE . "Tebex Server" . TextFormat::RESET . TextFormat::EOL .
						TextFormat::WHITE . "ID: " . TextFormat::GRAY . $server->getId() . TextFormat::EOL .
						TextFormat::WHITE . "Name: " . TextFormat::GRAY . $server->getName() . TextFormat::EOL .
						"" . TextFormat::EOL .
						TextFormat::BOLD . TextFormat::WHITE . "Tebex API" . TextFormat::RESET . TextFormat::EOL .
						TextFormat::WHITE . "Latency: " . TextFormat::GRAY . round($this->plugin->getApi()->getLatency() * 1000) . "ms" . TextFormat::EOL .
						"" . TextFormat::EOL
					);
					return true;
				case "forcecheck":
				case "refresh":
					static $command_senders = null;
					if($command_senders === null){
						$command_senders = [];
						$this->handler->getDueCommandsHandler()->refresh(static function(int $offline_commands, int $online_players) use(&$command_senders) : void{
							foreach($command_senders as $sender){
								if(!($sender instanceof Player) || $sender->isOnline()){
									$sender->sendMessage(
										TextFormat::WHITE . "Refreshed command queue" . TextFormat::EOL .
										TextFormat::WHITE . "Offline commands fetched: " . TextFormat::GRAY . $offline_commands . TextFormat::EOL .
										TextFormat::WHITE . "Online players due: " . TextFormat::GRAY . $online_players
									);
								}
							}
							$command_senders = null;
						});
					}

					$command_senders[spl_object_id($sender)] = $sender;
					$sender->sendMessage(TextFormat::GRAY . "Refreshing command queue...");
					return true;
				case "secret":
					if(isset($args[1])){
						$this->onTypeSecret($sender, $command, $label, $args[1]);
					}else{
						$sender->sendMessage("Usage: /" . $label . " " . $args[0] . " <secret>");
					}
					return true;
			}
		}

		$sender->sendMessage(
			TextFormat::BOLD . TextFormat::WHITE . "Tebex Commands" . TextFormat::RESET . TextFormat::EOL .
			TextFormat::WHITE . "/" . $label . " secret" . TextFormat::GRAY . " - Set Tebex server secret" . TextFormat::EOL .
			TextFormat::WHITE . "/" . $label . " info" . TextFormat::GRAY . " - Fetch Tebex account, server and API info" . TextFormat::EOL .
			TextFormat::WHITE . "/" . $label . " refresh" . TextFormat::GRAY . " - Refresh offline and online command queues" . TextFormat::EOL
		);
		return false;
	}
}