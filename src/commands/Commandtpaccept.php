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

use Doma\EssentialsZ\teleport\TpaRequest;
use Doma\EssentialsZ\session\User;
use pocketmine\Server;
use function count;
use function strtolower;

class Commandtpaccept extends EssentialsCommand{

	public function __construct(){
		parent::__construct("tpaccept");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$acceptAll = count($args) > 0 && ($args[0] === "*" || strtolower($args[0]) === "all");

		if(!$user->hasPendingTpaRequests()){
			throw new TranslatableException("noPendingRequest");
		}

		if($acceptAll){
			$count = 0;
			while(($request = $user->getNextTpaRequest()) !== null){
				try{
					$this->handleTeleport($server, $user, $request);
					$count++;
				}catch(TranslatableException $e){
					$user->sendMessage($e->getMessage());
				}finally{
					$user->removeTpaRequest($request->name);
				}
			}
			$user->sendTl("requestAcceptedAll", $count);
			return;
		}

		if(count($args) > 0){
			$request = $user->getOutstandingTpaRequest($this->getPlayer($server, $user->getSource(), $args[0])->getName());
		}else{
			$request = $user->getNextTpaRequest();
		}

		$user->sendTl("requestAccepted");
		$this->handleTeleport($server, $user, $request);
	}

	private function handleTeleport(Server $server, User $user, ?TpaRequest $request) : void{
		if($request === null){
			throw new TranslatableException("noPendingRequest");
		}

		$requesterPlayer = $server->getPlayerExact($request->name);
		if($requesterPlayer === null || !$requesterPlayer->isConnected()){
			$user->removeTpaRequest($request->name);
			throw new TranslatableException("noPendingRequest");
		}
		$requester = $this->ess->getUser($requesterPlayer);

		if($request->here && !$requester->isAuthorized("essentialsz.tpahere")){
			throw new TranslatableException("noPendingRequest");
		}
		if(!$request->here && !$requester->isAuthorized("essentialsz.tpa")){
			throw new TranslatableException("noPendingRequest");
		}

		$requester->sendTl("requestAcceptedFrom", $user->getDisplayName());

		if($request->here){
			// the requester asked the accepter to come to where the request was made
			if($request->location === null){
				throw new TranslatableException("noPendingRequest");
			}
			$user->getBase()->teleport($request->location);
			$user->sendTl("teleporting");
		}else{
			$requester->getBase()->teleport($user->getBase()->getLocation());
			$requester->sendTl("teleporting");
		}
		$user->removeTpaRequest($request->name);
	}
}
