<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\due;

use tebexio\pocketmine\api\queue\TebexDuePlayer;
use tebexio\pocketmine\api\queue\TebexDuePlayersInfo;
use tebexio\pocketmine\api\queue\commands\online\TebexQueuedOnlineCommandsInfo;
use tebexio\pocketmine\handler\due\playerslist\TebexDuePlayerHolder;
use tebexio\pocketmine\handler\due\session\TebexPlayerSession;
use tebexio\pocketmine\TebexPlugin;
use tebexio\pocketmine\handler\TebexHandler;
use tebexio\pocketmine\handler\due\playerslist\TebexDuePlayersList;
use tebexio\pocketmine\handler\due\playerslist\TebexDuePlayersListListener;
use tebexio\pocketmine\handler\due\playerslist\OfflineTebexDuePlayersList;
use tebexio\pocketmine\handler\due\playerslist\OnlineTebexDuePlayersList;
use tebexio\pocketmine\thread\response\TebexResponseHandler;
use Closure;
use InvalidArgumentException;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

final class TebexDueCommandsHandler{

	/**
	 * @param string $game_type
	 * @param Closure $on_match
	 *
	 * @phpstan-param Closure(Player, TebexDuePlayerHolder) : void $on_match
	 *
	 * @return TebexDuePlayersList
	 */
	private static function getListFromGameType(string $game_type, Closure $on_match) : TebexDuePlayersList{
		switch($game_type){
			case "Minecraft (Bedrock)":
				if(!Server::getInstance()->getOnlineMode()){
					throw new InvalidArgumentException("xbox-auth must be enabled in server.properties");
				}
				return new OnlineTebexDuePlayersList($on_match);
			case "Minecraft Offline":
				return new OfflineTebexDuePlayersList($on_match);
			default:
				throw new InvalidArgumentException("Unsupported game server type " . $game_type);
		}
	}

	/** @var TebexPlugin */
	private $plugin;

	/** @var TebexDueOfflineCommandsHandler */
	private $offline_commands_handler;

	/** @var TebexDuePlayersList */
	private $list;

	/** @var bool */
	private $is_idle = true;

	public function __construct(TebexPlugin $plugin, TebexHandler $handler){
		TebexPlayerSession::init($plugin);

		$this->plugin = $plugin;
		$this->offline_commands_handler = new TebexDueOfflineCommandsHandler($plugin, $handler);

		$api = $plugin->getApi();
		$this->list = self::getListFromGameType($plugin->getInformation()->getAccount()->getGameType(), function(Player $player, TebexDuePlayerHolder $holder) use($api, $handler) : void{
			$session = $this->list->getSession($player);
			assert($session !== null);
			$api->getQueuedOnlineCommands($holder->getPlayer()->getId(), TebexResponseHandler::onSuccess(function(TebexQueuedOnlineCommandsInfo $info) use($player, $session, $holder, $handler) : void{
				if($player->isOnline()){
					$commands = $info->getCommands();
					$total_commands = count($commands);
					$timestamp = microtime(true);
					foreach($commands as $tebex_command){
						$session->executeOnlineCommand($tebex_command, $holder->getPlayer(), function(bool $success) use($tebex_command, $handler, &$total_commands, $player, $holder, $timestamp) : void{
							$command_string = $tebex_command->getCommand()->asOnlineFormattedString($player, $holder->getPlayer());
							if($success){
								$handler->queueCommandDeletion($tebex_command->getId());
								if(--$total_commands === 0){
									$current_holder = $this->list->get($player);
									if($current_holder !== null && $current_holder->getCreated() < $timestamp){
										$this->list->remove($current_holder);
									}
								}
								$this->plugin->getLogger()->debug("[/QUEUE] Executed online command: " . $command_string);
							}else{
								$this->plugin->getLogger()->warning("[/QUEUE] Failed to execute online command: " . $command_string);
							}
						});
					}
				}
			}));
		});

		$plugin->getServer()->getPluginManager()->registerEvents(new TebexDuePlayersListListener($this->list), $plugin);
		$plugin->getServer()->getPluginManager()->registerEvents(new TebexLazyDueCommandsListener($this), $plugin);
	}

	private function scheduleDuePlayersCheck() : bool{
		if($this->is_idle){
			$this->is_idle = false;
			$server = $this->plugin->getServer();
			$this->checkDuePlayers(function() use($server) : bool{
				if(count($server->getOnlinePlayers()) === 0){
					$this->is_idle = true;
					$this->plugin->getLogger()->debug("[/QUEUE] Online commands handler is now idle");
					return false;
				}
				return true;
			});
			return true;
		}
		return false;
	}

	public function getList() : TebexDuePlayersList{
		return $this->list;
	}

	public function refresh(?Closure $callback = null) : void{
		$this->offline_commands_handler->check(function(int $offline_cmds_count) use($callback) : void{
			$this->checkDuePlayers(null, static function(int $due_players_count) use($offline_cmds_count, $callback) : void{
				if($callback !== null){
					$callback($offline_cmds_count, $due_players_count);
				}
			});
		});
	}

	public function checkDuePlayers(?Closure $reschedule_condition = null, ?Closure $callback = null) : void{
		$this->plugin->getApi()->getDuePlayersList(TebexResponseHandler::onSuccess(function(TebexDuePlayersInfo $result) use($reschedule_condition, $callback) : void{
			$this->onFetchDuePlayers($result);
			if($callback !== null){
				$callback(count($result->getPlayers()));
			}
			if($reschedule_condition !== null && $reschedule_condition()){
				$next_check = $result->getMeta()->getNextCheck();
				$this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($reschedule_condition, $callback) : void{ $this->checkDuePlayers($reschedule_condition, $callback); }), $next_check * 20);
			}
		}));
	}

	private function onFetchDuePlayers(TebexDuePlayersInfo $result) : void{
		$players = $result->getPlayers();
		$this->list->update($players);

		$players_c = count($players);
		$this->plugin->getLogger()->debug("[/QUEUE] " . $players_c . " player" . ($players_c === 1 ? " is " : "s are") . " in the online commands queue");
	}

	public function onPlayerJoin() : void{
		if($this->scheduleDuePlayersCheck()){
			$this->plugin->getLogger()->debug("[/QUEUE] Online commands handler is no longer idle");
		}
	}
}