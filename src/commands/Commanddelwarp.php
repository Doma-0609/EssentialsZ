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

class Commanddelwarp extends EssentialsCommand{

	public function __construct(){
		parent::__construct("delwarp");
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) === 0){
			throw new NotEnoughArgumentsException();
		}

		$warps = $this->ess->getWarps();
		$warp = $warps->getWarp($args[0]);
		if($warp === null){
			throw new TranslatableException("warpNotExist");
		}
		$warps->removeWarp($warp->name);
		$sender->sendTl("deleteWarp", $warp->name);
	}
}
