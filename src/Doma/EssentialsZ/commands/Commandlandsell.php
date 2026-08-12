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
use function is_numeric;
use function mb_strtolower;

/**
 * Standalone /landsell: sells the claim you stand in, or one by number.
 */
class Commandlandsell extends EssentialsCommand{

	public function __construct(){
		parent::__construct("landsell");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$land = $this->ess->getLand();

		if(count($args) > 0 && is_numeric($args[0])){
			$found = $land->getLandById((int) $args[0]);
		}elseif(count($args) === 0 || mb_strtolower($args[0]) === "here"){
			$position = $user->getBase()->getPosition();
			$found = $land->getLandAt($user->getBase()->getWorld()->getFolderName(), $position->getFloorX(), $position->getFloorZ());
		}else{
			throw new NotEnoughArgumentsException();
		}

		if($found === null){
			throw new TranslatableException("landInvalidLand");
		}
		if(!$found->isOwner($user->getName()) && !$user->isAuthorized("essentialsz.land.bypass")){
			throw new TranslatableException("landNotYours");
		}
		$land->remove($found);
		$user->sendTl("landSold", $found->id);
	}
}
