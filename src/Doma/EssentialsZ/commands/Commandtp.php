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
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\Server;
use function count;

class Commandtp extends EssentialsCommand{

	public function __construct(){
		parent::__construct("tp");
	}

	public function getAlternatePermissions() : array{
		return [DefaultPermissionNames::COMMAND_TELEPORT_SELF, DefaultPermissionNames::COMMAND_TELEPORT_OTHER];
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		switch(count($args)){
			case 0:
				throw new NotEnoughArgumentsException();
			case 1:
				$player = $this->getPlayer($server, $user->getSource(), $args[0]);
				$user->sendTl("teleportToPlayer", $player->getDisplayName());
				$user->getBase()->teleport($player->getBase()->getLocation());
				break;
			case 3:
				$this->checkPositionPermission($user);
				$location = $user->getBase()->getLocation();
				$x = self::parseCoordinate($args[0], $location->x);
				$y = self::parseCoordinate($args[1], $location->y);
				$z = self::parseCoordinate($args[2], $location->z);
				self::checkCoordinateRange($user->getSource(), $x, $y, $z);
				$user->getBase()->teleport(new Location($x, $y, $z, $location->getWorld(), $location->yaw, $location->pitch));
				$user->sendTl("teleporting");
				break;
			case 4:
				$this->checkOthersPermission($user);
				$this->checkPositionPermission($user);
				$target = $this->getPlayer($server, $user->getSource(), $args[0]);
				$location = $target->getBase()->getLocation();
				$x = self::parseCoordinate($args[1], $location->x);
				$y = self::parseCoordinate($args[2], $location->y);
				$z = self::parseCoordinate($args[3], $location->z);
				self::checkCoordinateRange($user->getSource(), $x, $y, $z);
				$user->sendTl("teleporting");
				$target->getBase()->teleport(new Location($x, $y, $z, $location->getWorld(), $location->yaw, $location->pitch));
				$target->sendTl("teleporting");
				break;
			default:
				$this->checkOthersPermission($user);
				$target = $this->getPlayer($server, $user->getSource(), $args[0]);
				$toPlayer = $this->getPlayer($server, $user->getSource(), $args[1]);
				$target->sendTl("teleportAtoB", $user->getDisplayName(), $toPlayer->getDisplayName());
				$target->getBase()->teleport($toPlayer->getBase()->getLocation());
				break;
		}
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) < 2){
			throw new NotEnoughArgumentsException();
		}

		$target = $this->getPlayer($server, $sender, $args[0]);
		if(count($args) === 2){
			$toPlayer = $this->getPlayer($server, $sender, $args[1]);
			$target->sendTl("teleportAtoB", $sender->getDisplayName(), $toPlayer->getDisplayName());
			$target->getBase()->teleport($toPlayer->getBase()->getLocation());
		}elseif(count($args) > 3){
			$location = $target->getBase()->getLocation();
			$x = self::parseCoordinate($args[1], $location->x);
			$y = self::parseCoordinate($args[2], $location->y);
			$z = self::parseCoordinate($args[3], $location->z);
			self::checkCoordinateRange($sender, $x, $y, $z);
			$sender->sendTl("teleporting");
			$target->getBase()->teleport(new Location($x, $y, $z, $location->getWorld(), $location->yaw, $location->pitch));
			$target->sendTl("teleporting");
		}else{
			throw new NotEnoughArgumentsException();
		}
	}

	private function checkOthersPermission(User $user) : void{
		if(!$user->isAuthorized("essentialsz.tp.others")
			&& !$user->isAuthorized(DefaultPermissionNames::COMMAND_TELEPORT_OTHER)){
			throw new TranslatableException("noPerm", "essentialsz.tp.others");
		}
	}

	private function checkPositionPermission(User $user) : void{
		if(!$user->isAuthorized("essentialsz.tp.position")
			&& !$user->isAuthorized(DefaultPermissionNames::COMMAND_TELEPORT_SELF)){
			throw new TranslatableException("noPerm", "essentialsz.tp.position");
		}
	}
}
