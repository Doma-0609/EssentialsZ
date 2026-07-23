<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\commands;

use Doma\EssentialsZ\session\CommandSource;
use Doma\EssentialsZ\session\User;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\Server;
use function count;

class Commandheal extends EssentialsLoopCommand{

	public function __construct(){
		parent::__construct("heal");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) > 0 && $user->isAuthorized("essentialsz.heal.others")){
			$this->loopOnlinePlayersConsumer($server, $user->getSource(), true, true, $args[0],
				fn(User $target) => $this->updatePlayer($server, $user->getSource(), $target, $args));
			return;
		}

		$this->updatePlayer($server, $user->getSource(), $user, $args);
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) < 1){
			throw new NotEnoughArgumentsException();
		}

		$this->loopOnlinePlayersConsumer($server, $sender, true, true, $args[0],
			fn(User $target) => $this->updatePlayer($server, $sender, $target, $args));
	}

	protected function updatePlayer(Server $server, CommandSource $sender, User $user, array $args) : void{
		$player = $user->getBase();

		if($player->getHealth() <= 0){
			throw new TranslatableException("healDead");
		}

		$regainEvent = new EntityRegainHealthEvent($player, $player->getMaxHealth() - $player->getHealth(), EntityRegainHealthEvent::CAUSE_CUSTOM);
		$player->heal($regainEvent);
		if($regainEvent->isCancelled()){
			return;
		}

		$player->getHungerManager()->setFood(20.0);
		$player->extinguish();
		$player->setAirSupplyTicks($player->getMaxAirSupplyTicks());
		if($this->ess->getSettings()->isRemovingEffectsOnHeal()){
			$player->getEffects()->clear();
		}

		$user->sendTl("heal");
		if(!$sender->isPlayer() || $sender->getPlayer() !== $player){
			$sender->sendTl("healOther", $user->getDisplayName());
		}
	}
}
