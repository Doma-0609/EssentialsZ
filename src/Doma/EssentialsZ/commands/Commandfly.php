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

class Commandfly extends EssentialsToggleCommand{

	public function __construct(){
		parent::__construct("fly", "essentialsz.fly.others");
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		$this->toggleOtherPlayers($server, $sender, $args);
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$this->handleToggleWithArgs($server, $user, $args);
	}

	protected function togglePlayer(CommandSource $sender, User $user, ?bool $enabled) : void{
		$enabled ??= !$user->getBase()->getAllowFlight();

		$base = $user->getBase();
		$base->resetFallDistance();
		$base->setAllowFlight($enabled);
		$user->setFlyModeEnabled($enabled);
		if(!$base->getAllowFlight()){
			$base->setFlying(false);
		}

		$state = $user->playerTl($enabled ? "enabled" : "disabled");
		$user->sendTl("flyMode", $state, $user->getDisplayName());
		if(!$sender->isPlayer() || $sender->getPlayer() !== $base){
			$sender->sendTl("flyMode", $sender->tl($enabled ? "enabled" : "disabled"), $user->getDisplayName());
		}
	}
}
