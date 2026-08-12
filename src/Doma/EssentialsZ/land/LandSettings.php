<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\land;

use Doma\EssentialsZ\config\EssentialsConfiguration;
use function array_map;
use function max;
use function strtolower;

final class LandSettings{

	/**
	 * @param list<string> $protectedWorlds worlds where unclaimed land is also protected
	 * @param list<string> $nonCheckWorlds  worlds skipped entirely (performance)
	 * @param list<string> $buyDisallowedWorlds
	 */
	public function __construct(
		public readonly float $pricePerBlock,
		public readonly int $minSize,
		public readonly int $maxSize,
		public readonly int $maxPerPlayer,
		public readonly bool $showBorder,
		public readonly bool $allowMove,
		public readonly array $protectedWorlds,
		public readonly array $nonCheckWorlds,
		public readonly array $buyDisallowedWorlds
	){}

	public static function fromConfig(EssentialsConfiguration $config) : self{
		return new self(
			max(0.0, $config->getDouble("land.price-per-block", 10.0)),
			max(1, (int) $config->getLong("land.min-size", 1)),
			max(1, (int) $config->getLong("land.max-size", 64)),
			max(0, (int) $config->getLong("land.max-per-player", 0)),
			$config->getBoolean("land.show-border", true),
			$config->getBoolean("land.allow-move", true),
			self::worldList($config, "land.protected-worlds"),
			self::worldList($config, "land.non-check-worlds"),
			self::worldList($config, "land.buying-disallowed-worlds")
		);
	}

	/**
	 * @return list<string>
	 */
	private static function worldList(EssentialsConfiguration $config, string $path) : array{
		return array_map(static fn(string $w) => strtolower($w), $config->getList($path, []));
	}
}
