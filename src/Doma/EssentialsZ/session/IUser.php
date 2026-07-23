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

use pocketmine\player\Player;

interface IUser{

	public function getBase() : Player;

	public function getName() : string;

	public function getDisplayName() : string;

	public function isAuthorized(string $node) : bool;

	public function sendMessage(string $message) : void;

	/**
	 * Sends a translated message, honouring per-player locale.
	 */
	public function sendTl(string $tlKey, string|int|float ...$args) : void;

	/**
	 * Translates a message in this player's locale (no send).
	 */
	public function playerTl(string $tlKey, string|int|float ...$args) : string;

	public function getSource() : CommandSource;
}
