<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\teleport;

use pocketmine\entity\Location;

final class TpaRequest{

	public function __construct(
		public readonly string $name,
		public readonly string $displayName,
		public readonly bool $here,
		public readonly int $time,
		public readonly ?Location $location
	){}
}
