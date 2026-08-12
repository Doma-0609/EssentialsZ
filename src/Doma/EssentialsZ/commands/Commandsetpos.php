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

/**
 * Standalone /startp and /endp: mark the corners of the area to buy, shared
 * with /land pos1 and /land pos2.
 */
class Commandsetpos extends EssentialsCommand{

	public function __construct(string $name, private bool $first){
		parent::__construct($name);
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$position = $user->getBase()->getPosition();
		$this->ess->getLand()->setSelection(
			$user->getName(),
			$user->getBase()->getWorld()->getFolderName(),
			$this->first,
			$position->getFloorX(),
			$position->getFloorZ()
		);
		$user->sendTl($this->first ? "landPos1" : "landPos2", $position->getFloorX(), $position->getFloorZ());
	}
}
