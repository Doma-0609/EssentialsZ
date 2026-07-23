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
use function strtolower;

class Commandtpdeny extends EssentialsCommand{

	public function __construct(){
		parent::__construct("tpdeny");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$denyAll = count($args) > 0 && ($args[0] === "*" || strtolower($args[0]) === "all");

		if(!$user->hasPendingTpaRequests()){
			throw new TranslatableException("noPendingRequest");
		}

		if($denyAll){
			foreach($user->getPendingTpaKeys() as $key){
				$this->denyRequest($server, $user, $key);
			}
			return;
		}

		if(count($args) > 0){
			$name = $this->getPlayer($server, $user->getSource(), $args[0])->getName();
		}else{
			$next = $user->getNextTpaRequest();
			if($next === null){
				throw new TranslatableException("noPendingRequest");
			}
			$name = $next->name;
		}
		$this->denyRequest($server, $user, $name);
	}

	private function denyRequest(Server $server, User $user, string $name) : void{
		$request = $user->getOutstandingTpaRequest($name);
		if($request === null){
			throw new TranslatableException("noPendingRequest");
		}

		$user->sendTl("requestDenied");
		$requesterPlayer = $server->getPlayerExact($request->name);
		if($requesterPlayer !== null){
			$this->ess->getUser($requesterPlayer)->sendTl("requestDeniedFrom", $user->getDisplayName());
		}
		$user->removeTpaRequest($request->name);
	}
}
