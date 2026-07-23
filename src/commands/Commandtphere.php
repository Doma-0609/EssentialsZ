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

class Commandtphere extends EssentialsCommand{

	public function __construct(){
		parent::__construct("tphere");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$player = $this->getPlayerAt($server, $user->getSource(), $args, 0);
		$player->getBase()->teleport($user->getBase()->getLocation());
		$player->sendTl("teleporting");
		$user->sendTl("teleporting");
	}
}
