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

use Doma\EssentialsZ\session\CommandSource;
use pocketmine\Server;
use function count;
use function mb_strtolower;

class Commandeco extends EconomyAdminCommand{

	public function __construct(){
		parent::__construct("eco");
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) < 2){
			throw new NotEnoughArgumentsException();
		}
		$this->applyOperation($server, $sender, mb_strtolower($args[0]), $args[1], $args[2] ?? null);
	}
}
