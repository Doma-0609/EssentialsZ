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
use Doma\EssentialsZ\session\User;
use pocketmine\Server;
use function count;
use function floor;
use function microtime;

class Commandback extends EssentialsCommand{

	public function __construct(){
		parent::__construct("back");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) > 0 && $user->isAuthorized("essentialsz.back.others")){
			$this->parseOthers($server, $user->getSource(), $args);
			return;
		}
		$this->teleportBack($user->getSource(), $user);
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) === 0){
			throw new NotEnoughArgumentsException();
		}
		$this->parseOthers($server, $sender, $args);
	}

	private function parseOthers(Server $server, CommandSource $sender, array $args) : void{
		$player = $this->getPlayer($server, $sender, $args[0]);
		$sender->sendTl("backOther", $player->getName());
		$this->teleportBack($sender, $player);
	}

	private function teleportBack(CommandSource $sender, User $user) : void{
		$location = $user->getLastDeathLocation();
		if($location === null){
			throw new TranslatableException("noLocationFound");
		}

		$limit = $this->ess->getSettings()->getBackDeathTimeLimit();
		if($limit > 0 && ((int) floor(microtime(true) * 1000)) - $user->getLastDeathTime() > $limit * 1000){
			throw new TranslatableException("noLocationFound");
		}

		$user->sendTl("backUsageMsg");
		$user->getBase()->teleport($location);
	}
}
