<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\config;

use pocketmine\utils\Config;
use function array_is_list;
use function array_key_exists;
use function copy;
use function is_array;
use function is_file;

/**
 * Adds config keys introduced by a plugin update to a server's existing
 * config.yml, keeping every value the admin already set.
 *
 * Driven by the "config-version" key: an on-disk file older than the bundled
 * default is filled in with any missing keys and stamped with the new version,
 * so a later start with a current file (or an admin who deleted a key) is left
 * alone. The original file is backed up to config.yml.bak first.
 */
final class ConfigUpdater{

	private function __construct(){
	}

	/**
	 * @return int the number of keys added (0 when nothing changed)
	 */
	public static function update(string $defaultResource, string $liveFile) : int{
		if(!is_file($liveFile) || !is_file($defaultResource)){
			return 0; // a fresh install already has the current default
		}
		$default = (new Config($defaultResource, Config::YAML))->getAll();
		$live = new Config($liveFile, Config::YAML);
		$current = $live->getAll();

		if((int) ($current["config-version"] ?? 0) >= (int) ($default["config-version"] ?? 0)){
			return 0;
		}

		$added = self::fillMissing($default, $current);
		$current["config-version"] = (int) ($default["config-version"] ?? 0);

		@copy($liveFile, $liveFile . ".bak");
		$live->setAll($current);
		$live->save();
		return $added;
	}

	/**
	 * Recursively copies keys present in $default but missing from $target,
	 * leaving existing values (and list values) untouched.
	 *
	 * @param array<string, mixed> $default
	 * @param array<string, mixed> $target
	 */
	public static function fillMissing(array $default, array &$target) : int{
		$added = 0;
		foreach($default as $key => $value){
			if(!array_key_exists($key, $target)){
				$target[$key] = $value;
				$added++;
			}elseif(is_array($value) && is_array($target[$key]) && !array_is_list($value)){
				$added += self::fillMissing($value, $target[$key]);
			}
		}
		return $added;
	}
}
