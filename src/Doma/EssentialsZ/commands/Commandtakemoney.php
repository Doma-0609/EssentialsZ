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

class Commandtakemoney extends EconomyAdminCommand{

	public function __construct(){
		parent::__construct("takemoney");
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) < 2){
			throw new NotEnoughArgumentsException();
		}
		$this->applyOperation($server, $sender, "take", $args[0], $args[1]);
	}
}
