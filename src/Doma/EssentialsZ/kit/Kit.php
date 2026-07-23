<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\kit;

final class Kit{

	public const ICON_NONE = -1;
	public const ICON_PATH = 0;
	public const ICON_URL = 1;

	/**
	 * @param float        $delay    cooldown in seconds; negative = single use
	 * @param list<array|string> $items    serialized items (nbt_b64 maps or "name [count]" strings)
	 * @param list<array|string> $armor    serialized armor pieces
	 * @param list<string> $commands console commands run on claim; {player} and
	 *                               {display-name} are replaced with the receiver
	 */
	public function __construct(
		public readonly string $name,
		public readonly string $displayName,
		public readonly float $delay,
		public readonly float $cost,
		public readonly int $iconType,
		public readonly string $icon,
		public readonly array $items,
		public readonly array $armor,
		public readonly array $commands
	){}
}
