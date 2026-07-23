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
use pocketmine\world\World;
use function count;
use function fmod;
use function is_numeric;

class Commandtppos extends EssentialsCommand{

	public function __construct(){
		parent::__construct("tppos");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) < 3){
			throw new NotEnoughArgumentsException();
		}

		$location = $user->getBase()->getLocation();
		$x = self::parseCoordinate($args[0], $location->x);
		$y = self::parseCoordinate($args[1], $location->y);
		$z = self::parseCoordinate($args[2], $location->z);
		$world = $location->getWorld();
		$yaw = $location->yaw;
		$pitch = $location->pitch;

		if(count($args) === 4){
			$world = $this->matchWorld($server, $args[3]);
		}
		if(count($args) > 4){
			$yaw = fmod(self::parseRotation($args[3]) + 360.0, 360.0);
			$pitch = self::parseRotation($args[4]);
		}
		if(count($args) > 5){
			$world = $this->matchWorld($server, $args[5]);
		}
		self::checkCoordinateRange($user->getSource(), $x, $y, $z);

		$user->sendTl("teleporting");
		$user->getBase()->teleport(new Location($x, $y, $z, $world, $yaw, $pitch));
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) < 4){
			throw new NotEnoughArgumentsException();
		}

		$target = $this->getPlayer($server, $sender, $args[0]);
		$location = $target->getBase()->getLocation();
		$x = self::parseCoordinate($args[1], $location->x);
		$y = self::parseCoordinate($args[2], $location->y);
		$z = self::parseCoordinate($args[3], $location->z);
		$world = $location->getWorld();
		$yaw = $location->yaw;
		$pitch = $location->pitch;

		if(count($args) === 5){
			$world = $this->matchWorld($server, $args[4]);
		}
		if(count($args) > 5){
			$yaw = fmod(self::parseRotation($args[4]) + 360.0, 360.0);
			$pitch = self::parseRotation($args[5]);
		}
		if(count($args) > 6){
			$world = $this->matchWorld($server, $args[6]);
		}
		self::checkCoordinateRange($sender, $x, $y, $z);

		$sender->sendTl("teleporting");
		$target->sendTl("teleporting");
		$target->getBase()->teleport(new Location($x, $y, $z, $world, $yaw, $pitch));
	}

	private function matchWorld(Server $server, string $name) : World{
		$world = $server->getWorldManager()->getWorldByName($name);
		if($world === null){
			throw new TranslatableException("invalidWorld");
		}
		return $world;
	}

	private static function parseRotation(string $arg) : float{
		if(!is_numeric($arg)){
			throw new NotEnoughArgumentsException();
		}
		return (float) $arg;
	}
}
