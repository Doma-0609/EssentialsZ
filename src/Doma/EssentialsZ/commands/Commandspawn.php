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
use pocketmine\entity\Location;
use pocketmine\Server;
use function count;

class Commandspawn extends EssentialsCommand{

	public function __construct(){
		parent::__construct("spawn");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) > 0 && $user->isAuthorized("essentialsz.spawn.others")){
			$target = $this->getPlayer($server, $user->getSource(), $args[0]);
			$this->teleportToSpawn($server, $target);
			$target->sendTl("teleportAtoB", $user->getDisplayName(), "spawn");
			return;
		}
		$this->teleportToSpawn($server, $user);
		$user->sendTl("teleporting");
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) === 0){
			throw new NotEnoughArgumentsException();
		}
		$target = $this->getPlayer($server, $sender, $args[0]);
		$this->teleportToSpawn($server, $target);
		$target->sendTl("teleportAtoB", $sender->getDisplayName(), "spawn");
	}

	private function teleportToSpawn(Server $server, User $user) : void{
		$spawn = $this->ess->getSpawn()->getSpawn($server);
		if($spawn === null){
			$world = $server->getWorldManager()->getDefaultWorld();
			if($world === null){
				throw new TranslatableException("invalidWorld");
			}
			$safe = $world->getSafeSpawn();
			$spawn = Location::fromObject($safe, $world, 0.0, 0.0);
		}
		$user->getBase()->teleport($spawn);
	}
}
