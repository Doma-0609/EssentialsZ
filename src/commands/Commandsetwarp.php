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
use Doma\EssentialsZ\warp\Warp;
use Doma\EssentialsZ\warp\Warps;
use pocketmine\Server;
use function count;
use function is_numeric;
use function mb_strtolower;
use function trim;

class Commandsetwarp extends EssentialsCommand{

	public function __construct(){
		parent::__construct("setwarp");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) === 0){
			throw new NotEnoughArgumentsException();
		}

		$name = trim($args[0]);
		if($name === "" || is_numeric($name) || !Warps::isValidName($name)){
			throw new TranslatableException("invalidWarpName");
		}

		$warps = $this->ess->getWarps();
		$existing = $warps->getWarp($name);
		$location = $user->getBase()->getLocation();

		if($existing === null){
			$warps->setWarp($name, $name, Warp::ICON_NONE, "", $location);
		}elseif($user->isAuthorized("essentialsz.warp.overwrite")
			|| $user->isAuthorized("essentialsz.warp.overwrite." . mb_strtolower($existing->name))){
			$warps->setWarp($existing->name, $existing->displayName, $existing->iconType, $existing->icon, $location);
		}else{
			throw new TranslatableException("warpOverwrite");
		}
		$user->sendTl("warpSet", $name);
	}
}
