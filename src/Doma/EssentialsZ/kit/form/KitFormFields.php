<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\kit\form;

use Doma\EssentialsZ\kit\Kits;
use pocketmine\player\Player;
use function array_values;
use function is_numeric;
use function substr;
use function trim;

/**
 * Shared field parsing and inventory-snapshot helpers for the kit admin
 * forms.
 */
final class KitFormFields{

	public static function isNumericOrEmpty(string $value) : bool{
		return $value === "" || is_numeric($value);
	}

	/**
	 * Trims a single command line and strips a leading "/".
	 */
	public static function normalizeCommand(string $command) : string{
		$command = trim($command);
		return $command !== "" && $command[0] === "/" ? substr($command, 1) : $command;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function snapshotInventory(Player $player) : array{
		$items = [];
		foreach($player->getInventory()->getContents() as $item){
			$items[] = Kits::encodeItem($item);
		}
		return array_values($items);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function snapshotArmor(Player $player) : array{
		$armor = [];
		foreach($player->getArmorInventory()->getContents() as $item){
			$armor[] = Kits::encodeItem($item);
		}
		return array_values($armor);
	}
}
