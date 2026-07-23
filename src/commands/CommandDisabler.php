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

use pocketmine\command\SimpleCommandMap;
use function ltrim;
use function trim;

/**
 * Removes commands from the server on request. Runs last during boot, so it
 * reaches EssentialsZ's own commands and those of any plugin loaded before it.
 */
final class CommandDisabler{

	private function __construct(){
	}

	/**
	 * Unregisters every listed command. A label may be a command's main name or
	 * one of its aliases; either way the whole command goes, because the server
	 * drops every label that points at it.
	 *
	 * @param list<string> $labels
	 * @return list<string> the labels that matched no command
	 */
	public static function disable(SimpleCommandMap $commandMap, array $labels) : array{
		$missing = [];
		foreach($labels as $label){
			$label = ltrim(trim($label), "/");
			if($label === ""){
				continue;
			}
			$command = $commandMap->getCommand($label);
			if($command === null){
				$missing[] = $label;
				continue;
			}
			$commandMap->unregister($command);
		}
		return $missing;
	}
}
