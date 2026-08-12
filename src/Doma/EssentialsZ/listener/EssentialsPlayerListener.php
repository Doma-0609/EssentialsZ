<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use function floor;
use function microtime;
use Doma\EssentialsZ\IEssentials;

class EssentialsPlayerListener implements Listener{

	public function __construct(private IEssentials $ess){}

	/**
	 * @priority MONITOR
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$user = $this->ess->getUser($player);

		$user->setLastAccountName($player->getName());
		$user->setLastLogin(self::currentTimeMillis());
		$user->setXuid($player->getXuid());
		$user->save();

		if(!$player->hasPermission("essentialsz.vanish.see")){
			foreach($this->ess->getOnlineUsers() as $onlineUser){
				if($onlineUser->isVanished() && $onlineUser->getBase() !== $player){
					$onlineUser->hideFrom($player);
				}
			}
		}

		if($this->ess->getSettings()->isAutoVanish() && $player->hasPermission("essentialsz.vanish.onjoin")){
			$event->setJoinMessage("");
			$user->setVanished(true, false);
			$user->sendTl("vanished");
		}
	}

	/**
	 * @priority MONITOR
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		$user = $this->ess->getUser($player);

		if($user->isVanished()){
			// already announced as left when vanishing
			$event->setQuitMessage("");
			foreach($player->getServer()->getOnlinePlayers() as $viewer){
				$viewer->showPlayer($player);
			}
		}

		$user->setLastLogout(self::currentTimeMillis());
		$this->ess->getUsers()->invalidate($player);
	}

	/**
	 * @priority MONITOR
	 */
	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		$player = $event->getEntity();
		$this->ess->getUser($player)->setLastDeathLocation($player->getLocation(), self::currentTimeMillis());
	}

	public function onPlayerMove(PlayerMoveEvent $event) : void{
		$user = $this->ess->getUser($event->getPlayer());
		if($user->isAfk()){
			$user->updateAfkStatus(false);
		}
	}

	public function onPlayerChat(PlayerChatEvent $event) : void{
		$user = $this->ess->getUser($event->getPlayer());
		if($user->isAfk()){
			$user->updateAfkStatus(false);
		}
	}

	public static function currentTimeMillis() : int{
		return (int) floor(microtime(true) * 1000);
	}
}
