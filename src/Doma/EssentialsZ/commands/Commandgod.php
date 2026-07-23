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

class Commandgod extends EssentialsToggleCommand{

	public function __construct(){
		parent::__construct("god", "essentialsz.god.others");
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		$this->toggleOtherPlayers($server, $sender, $args);
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$this->handleToggleWithArgs($server, $user, $args);
	}

	protected function togglePlayer(CommandSource $sender, User $user, ?bool $enabled) : void{
		$enabled ??= !$user->isGodModeEnabled();

		$user->setGodModeEnabled($enabled);

		$base = $user->getBase();
		if($enabled && $base->getHealth() > 0){
			$base->setHealth($base->getMaxHealth());
			$base->getHungerManager()->setFood(20.0);
		}

		$user->sendTl("godMode", $user->playerTl($enabled ? "enabled" : "disabled"));
		if(!$sender->isPlayer() || $sender->getPlayer() !== $base){
			$sender->sendTl("godMode", $sender->tl($enabled ? "enabled" : "disabled"));
		}
	}
}
