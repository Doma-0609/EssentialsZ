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
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\Server;
use function count;

class Commandtpo extends EssentialsCommand{

	public function __construct(){
		parent::__construct("tpo");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		switch(count($args)){
			case 0:
				throw new NotEnoughArgumentsException();
			case 1:
				$player = $this->getPlayerOverride($user, $server, $args[0]);
				$user->getBase()->teleport($player->getBase()->getLocation());
				break;
			default:
				if(!$user->isAuthorized("essentialsz.tp.others")
					&& !$user->isAuthorized(DefaultPermissionNames::COMMAND_TELEPORT_OTHER)){
					throw new TranslatableException("noPerm", "essentialsz.tp.others");
				}
				$toPlayer = $this->getPlayerOverride($user, $server, $args[1]);
				foreach($this->overrideTargets($user, $server, $args[0]) as $target){
					$target->getBase()->teleport($toPlayer->getBase()->getLocation());
					$target->sendTl("teleportAtoB", $user->getDisplayName(), $toPlayer->getDisplayName());
				}
				break;
		}
	}

	/**
	 * Like matchTargets(), but a plain name reaches vanished players too.
	 *
	 * @return list<User>
	 */
	private function overrideTargets(User $user, Server $server, string $searchTerm) : array{
		$selected = $this->resolveSelector($user->getSource(), $searchTerm);
		if($selected !== null){
			if($selected === []){
				throw new PlayerNotFoundException();
			}
			return $selected;
		}
		return [$this->getPlayerOverride($user, $server, $searchTerm)];
	}

	private function getPlayerOverride(User $user, Server $server, string $searchTerm) : User{
		$selected = $this->resolveSelector($user->getSource(), $searchTerm);
		if($selected !== null){
			if($selected === []){
				throw new PlayerNotFoundException();
			}
			return $selected[0];
		}
		$player = $server->getPlayerExact($searchTerm) ?? $server->getPlayerByPrefix($searchTerm);
		if($player === null){
			throw new PlayerNotFoundException();
		}
		return $this->ess->getUser($player);
	}
}
