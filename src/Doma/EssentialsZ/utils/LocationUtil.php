<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\utils;

use pocketmine\entity\Location;
use pocketmine\Server;
use function is_array;

final class LocationUtil{

	public static function toMap(Location $location) : array{
		return [
			"world" => $location->getWorld()->getFolderName(),
			"x" => $location->getX(),
			"y" => $location->getY(),
			"z" => $location->getZ(),
			"yaw" => $location->getYaw(),
			"pitch" => $location->getPitch()
		];
	}

	public static function fromMap(mixed $map, Server $server) : ?Location{
		if(!is_array($map) || !isset($map["world"], $map["x"], $map["y"], $map["z"])){
			return null;
		}
		$worldName = (string) $map["world"];
		$worldManager = $server->getWorldManager();
		$world = $worldManager->getWorldByName($worldName);
		if($world === null && $worldManager->loadWorld($worldName)){
			$world = $worldManager->getWorldByName($worldName);
		}
		if($world === null){
			return null;
		}
		return new Location(
			(float) $map["x"],
			(float) $map["y"],
			(float) $map["z"],
			$world,
			(float) ($map["yaw"] ?? 0.0),
			(float) ($map["pitch"] ?? 0.0)
		);
	}
}
