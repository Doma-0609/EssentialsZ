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

use Doma\EssentialsZ\ISettings;
use pocketmine\player\Player;
use function mb_strtolower;
use function trim;

final class UserStorageKey{

	public const NAME = "name";
	public const UUID = "uuid";
	public const XUID = "xuid";

	public static function resolve(ISettings $settings, Player $player) : string{
		return match($settings->getUserStorageKey()){
			self::UUID => $player->getUniqueId()->toString(),
			// XUID is empty on servers with xbox-auth disabled
			self::XUID => $player->getXuid() !== "" ? $player->getXuid() : self::nameKey($player),
			default => self::nameKey($player)
		};
	}

	public static function nameKey(Player $player) : string{
		return mb_strtolower(trim($player->getName()));
	}
}
