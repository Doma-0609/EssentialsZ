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

class Commandsetspawn extends EssentialsCommand{

	public function __construct(){
		parent::__construct("setspawn");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$this->ess->getSpawn()->setSpawn($user->getBase()->getLocation());
		$user->sendTl("spawnSet", "default");
	}
}
