<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\economy;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

final class EconomyListener implements Listener{

	public function __construct(private EssentialsEconomy $economy){}

	public function onJoin(PlayerJoinEvent $event) : void{
		$this->economy->createAccount($event->getPlayer()->getName());
	}
}
