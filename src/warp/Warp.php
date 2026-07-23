<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\warp;

use Doma\EssentialsZ\utils\LocationUtil;
use pocketmine\entity\Location;
use pocketmine\Server;

final class Warp{

	public const ICON_NONE = -1;
	public const ICON_PATH = 0;
	public const ICON_URL = 1;

	public function __construct(
		public readonly string $name,
		public readonly string $displayName,
		public readonly int $iconType,
		public readonly string $icon,
		public readonly array $locationMap
	){}

	public function getLocation(Server $server) : ?Location{
		return LocationUtil::fromMap($this->locationMap, $server);
	}
}
