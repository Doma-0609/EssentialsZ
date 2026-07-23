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

use Doma\EssentialsZ\session\User;
use pocketmine\Server;
use function count;

class Commandtpr extends EssentialsCommand{

	public function __construct(){
		parent::__construct("tpr");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$world = $user->getBase()->getWorld();
		if(count($args) > 0){
			$named = $server->getWorldManager()->getWorldByName($args[0]);
			if($named === null){
				throw new TranslatableException("invalidWorld");
			}
			$world = $named;
		}

		$target = $user;
		if(count($args) > 1 && $user->isAuthorized("essentialsz.tpr.others")){
			$target = $this->getPlayer($server, $user->getSource(), $args[1]);
		}

		$target->sendTl("tprSuccess");
		if($target !== $user){
			$user->sendTl("tprOtherUser", $target->getDisplayName());
		}

		$location = $this->ess->getRandomTeleport()->getRandomLocation($world);
		if($location === null){
			throw new TranslatableException("noLocationFound");
		}
		$target->getBase()->teleport($location);
		$target->sendTl("tprSuccessDone");
	}
}
