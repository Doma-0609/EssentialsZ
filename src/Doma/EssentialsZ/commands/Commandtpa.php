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
use function mb_strtolower;

class Commandtpa extends EssentialsCommand{

	public function __construct(){
		parent::__construct("tpa");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) < 1){
			throw new NotEnoughArgumentsException();
		}

		$player = $this->getPlayer($server, $user->getSource(), $args[0]);
		if(mb_strtolower($user->getName()) === mb_strtolower($player->getName())){
			throw new NotEnoughArgumentsException();
		}
		if(!$player->isAuthorized("essentialsz.tpaccept")){
			throw new TranslatableException("teleportNoAcceptPermission", $player->getDisplayName());
		}
		if($player->hasOutstandingTpaRequest($user->getName(), false)){
			throw new TranslatableException("requestSentAlready", $player->getDisplayName());
		}

		$player->requestTeleport($user, false);
		$player->sendTl("teleportRequest", $user->getDisplayName());
		$player->sendTl("typeTpaccept");
		$player->sendTl("typeTpdeny");
		$timeout = $this->ess->getSettings()->getTpaAcceptCancellation();
		if($timeout !== 0){
			$player->sendTl("teleportRequestTimeoutInfo", $timeout);
		}

		$user->sendTl("requestSent", $player->getDisplayName());
	}
}
