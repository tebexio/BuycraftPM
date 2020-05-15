<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\due\session;

use tebexio\pocketmine\api\queue\TebexDuePlayer;
use tebexio\pocketmine\api\queue\commands\online\TebexQueuedOnlineCommand;
use tebexio\pocketmine\handler\command\TebexCommandSender;
use tebexio\pocketmine\TebexPlugin;
use Closure;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskScheduler;

final class TebexPlayerSession{

	/** @var TaskScheduler */
	private static $scheduler;

	public static function init(TebexPlugin $plugin) : void{
		self::$scheduler = $plugin->getScheduler();
	}

	/** @var Player */
	private $player;

	/** @var DelayedOnlineCommandHandler[] */
	private $delayed_online_command_handlers = [];

	public function __construct(Player $player){
		$this->player = $player;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function destroy() : void{
		foreach($this->delayed_online_command_handlers as $handler){
			$handler->getHandler()->cancel();
		}
		$this->delayed_online_command_handlers = [];
	}

	public function executeOnlineCommand(TebexQueuedOnlineCommand $command, TebexDuePlayer $due_player, Closure $callback) : void{
		$conditions = $command->getConditions();
		$delay = $conditions->getDelay();
		if($delay > 0){
			$this->scheduleCommandForDelay($command, $due_player, $delay * 20, $callback);
		}else{
			$callback($this->instantlyExecuteOnlineCommand($command, $due_player));
		}
	}

	private function scheduleCommandForDelay(TebexQueuedOnlineCommand $command, TebexDuePlayer $due_player, int $delay, Closure $callback) : bool{
		if(!isset($this->delayed_online_command_handlers[$id = $command->getId()])){
			$this->delayed_online_command_handlers[$id] = new DelayedOnlineCommandHandler($command, self::$scheduler->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($command, $due_player, $callback) : void{
				$callback($this->instantlyExecuteOnlineCommand($command, $due_player));
			}), $delay));
			return true;
		}
		return false;
	}

	private function instantlyExecuteOnlineCommand(TebexQueuedOnlineCommand $command, TebexDuePlayer $due_player) : bool{
		$conditions = $command->getConditions();
		$slots = $conditions->getInventorySlots();
		if($slots > 0){
			$inventory = $this->player->getInventory();
			$free_slots = count($inventory->all(ItemFactory::get(ItemIds::AIR, 0, 0)));
			if($free_slots < $slots){
				return false;
			}
		}

		return $this->player->getServer()->dispatchCommand(TebexCommandSender::instance(), $command->getCommand()->asOnlineFormattedString($this->player, $due_player));
	}
}