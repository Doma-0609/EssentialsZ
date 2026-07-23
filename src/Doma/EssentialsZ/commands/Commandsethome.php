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
use function array_merge;
use function array_slice;
use function count;
use function explode;
use function in_array;
use function is_numeric;
use function mb_strtolower;
use function preg_match;

class Commandsethome extends EssentialsCommand{

	public function __construct(){
		parent::__construct("sethome");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$usersHome = $user;
		$name = "home";

		if(count($args) > 0){
			$nameParts = explode(":", $args[0], 2);
			if(isset($nameParts[1])){
				$args = array_merge($nameParts, array_slice($args, 1));
			}

			if(count($args) < 2){
				$name = mb_strtolower($args[0]);
			}else{
				$name = mb_strtolower($args[1]);
				if($user->isAuthorized("essentialsz.sethome.others")){
					$usersHome = $this->getPlayer($server, $user->getSource(), $args[0]);
				}
			}
		}

		if($name === "bed" || is_numeric($name) || preg_match("/^[a-z0-9_-]{1,32}$/", $name) !== 1){
			throw new TranslatableException("invalidHomeName");
		}

		$limit = $this->ess->getSettings()->getMaxHomes();
		if(!$user->isAuthorized("essentialsz.sethome.multiple.unlimited")
			&& !in_array($name, $usersHome->getHomes(), true)
			&& count($usersHome->getHomes()) >= $limit){
			throw new TranslatableException("maxHomes", $limit);
		}

		$location = $user->getBase()->getLocation();
		$usersHome->setHome($name, $location);
		$user->sendTl("homeSet", $location->getWorld()->getFolderName(), $location->getFloorX(), $location->getFloorY(), $location->getFloorZ(), $name);
	}
}
