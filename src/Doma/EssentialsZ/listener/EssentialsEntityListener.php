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

use pocketmine\event\entity\EntityCombustEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\player\Player;
use Doma\EssentialsZ\IEssentials;

class EssentialsEntityListener implements Listener{

	public function __construct(private IEssentials $ess){}

	public function onEntityDamage(EntityDamageEvent $event) : void{
		$entity = $event->getEntity();

		if($event instanceof EntityDamageByEntityEvent && $entity instanceof Player){
			$damager = $event->getDamager();
			if($damager instanceof Player){
				$attacker = $this->ess->getUser($damager);
				if($attacker->isGodModeEnabled() && !$attacker->isAuthorized("essentialsz.god.pvp")){
					$event->cancel();
					return;
				}
				if($attacker->isVanished() && !$attacker->isAuthorized("essentialsz.vanish.pvp")){
					$event->cancel();
					return;
				}
			}
		}

		if($entity instanceof Player && $this->ess->getUser($entity)->isGodModeEnabled()){
			$entity->extinguish();
			$entity->setAirSupplyTicks($entity->getMaxAirSupplyTicks());
			$event->cancel();
		}
	}

	public function onEntityCombust(EntityCombustEvent $event) : void{
		$entity = $event->getEntity();
		if($entity instanceof Player && $this->ess->getUser($entity)->isGodModeEnabled()){
			$event->cancel();
		}
	}

	public function onPlayerExhaust(PlayerExhaustEvent $event) : void{
		$human = $event->getPlayer();
		if($human instanceof Player && $this->ess->getUser($human)->isGodModeEnabled()){
			$event->cancel();
		}
	}
}
