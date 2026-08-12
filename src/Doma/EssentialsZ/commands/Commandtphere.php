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

class Commandtphere extends EssentialsCommand{

	public function __construct(){
		parent::__construct("tphere");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) === 0 || $args[0] === ""){
			throw new NotEnoughArgumentsException();
		}
		// Selectors such as @a bring every matched player to you.
		foreach($this->matchTargets($server, $user->getSource(), $args[0]) as $player){
			$player->getBase()->teleport($user->getBase()->getLocation());
			$player->sendTl("teleporting");
		}
		$user->sendTl("teleporting");
	}
}
