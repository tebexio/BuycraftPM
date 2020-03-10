<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\due\playerslist;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

final class TebexDuePlayersListListener implements Listener{

	/** @var TebexDuePlayersList */
	private $list;

	public function __construct(TebexDuePlayersList $list){
		$this->list = $list;
	}

	/**
	 * @param PlayerJoinEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$this->list->onPlayerJoin($event->getPlayer());
	}

	/**
	 * @param PlayerQuitEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$this->list->onPlayerQuit($event->getPlayer());
	}
}