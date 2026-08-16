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

class Commandspider extends EssentialsToggleCommand{

	public function __construct(){
		parent::__construct("spider", "essentialsz.spider.others");
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		$this->toggleOtherPlayers($server, $sender, $args);
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$this->handleToggleWithArgs($server, $user, $args);
	}

	protected function togglePlayer(CommandSource $sender, User $user, ?bool $enabled) : void{
		$base = $user->getBase();
		$enabled ??= !$base->canClimbWalls();
		$base->setCanClimbWalls($enabled);

		$state = $user->playerTl($enabled ? "enabled" : "disabled");
		$user->sendTl("spiderMode", $state, $user->getDisplayName());
		if(!$sender->isPlayer() || $sender->getPlayer() !== $base){
			$sender->sendTl("spiderMode", $sender->tl($enabled ? "enabled" : "disabled"), $user->getDisplayName());
		}
	}
}
