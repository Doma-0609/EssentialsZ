<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\rtl;

use Doma\EssentialsZ\config\EssentialsConfiguration;

final class RtlSettings{

	public function __construct(
		public readonly bool $shape
	){}

	public static function fromConfig(EssentialsConfiguration $config) : self{
		return new self($config->getBoolean("rtl.shape", true));
	}
}
