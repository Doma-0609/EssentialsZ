<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\economy;

use Doma\EssentialsZ\config\EssentialsConfiguration;
use function max;

final class EconomySettings{

	public function __construct(
		public readonly float $startMoney,
		public readonly float $maxMoney,
		public readonly string $currencySymbol,
		public readonly int $decimals,
		public readonly bool $allowPayOffline,
		public readonly float $minPayAmount
	){}

	public static function fromConfig(EssentialsConfiguration $config) : self{
		return new self(
			max(0.0, $config->getDouble("economy.start-money", 1000.0)),
			$config->getDouble("economy.max-money", 10000000000.0),
			$config->getString("economy.currency-symbol", "$") ?? "$",
			max(0, (int) $config->getLong("economy.decimals", 2)),
			$config->getBoolean("economy.allow-pay-offline", true),
			max(0.0, $config->getDouble("economy.min-pay-amount", 0.0))
		);
	}
}
