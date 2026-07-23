<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\session;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Doma\EssentialsZ\IEssentials;

/**
 * Wrapper around a command sender (player or console) with translation
 * helpers.
 */
class CommandSource{

	public function __construct(
		private CommandSender $sender,
		private IEssentials $ess
	){}

	public function getSender() : CommandSender{
		return $this->sender;
	}

	public function isPlayer() : bool{
		return $this->sender instanceof Player;
	}

	public function getPlayer() : ?Player{
		return $this->sender instanceof Player ? $this->sender : null;
	}

	public function getUser() : ?User{
		return $this->sender instanceof Player ? $this->ess->getUser($this->sender) : null;
	}

	public function getName() : string{
		return $this->sender->getName();
	}

	public function getDisplayName() : string{
		return $this->sender instanceof Player ? $this->sender->getDisplayName() : $this->sender->getName();
	}

	public function hasPermission(string $node) : bool{
		return $this->sender->hasPermission($node);
	}

	public function sendMessage(string $message) : void{
		if($message !== ""){
			$this->sender->sendMessage($message);
		}
	}

	public function tl(string $tlKey, string|int|float ...$args) : string{
		return $this->ess->getI18n()->tlPlayer($this->getPlayer(), $tlKey, ...$args);
	}

	public function sendTl(string $tlKey, string|int|float ...$args) : void{
		$this->sendMessage($this->tl($tlKey, ...$args));
	}
}
