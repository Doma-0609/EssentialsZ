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

use function in_array;

final class Category{

	public const ICON_NONE = -1;
	public const ICON_PATH = 0;
	public const ICON_URL = 1;

	/**
	 * @param bool         $locked true when essentialsz.category.<name> is required to view it
	 * @param list<string> $kits   names of the kits shown in this category
	 */
	public function __construct(
		public readonly string $name,
		public readonly string $displayName,
		public readonly int $iconType,
		public readonly string $icon,
		public readonly bool $locked,
		public readonly array $kits
	){}

	public function permissionNode() : string{
		return "essentialsz.category." . \mb_strtolower($this->name);
	}

	public function hasKit(string $kitName) : bool{
		return in_array($kitName, $this->kits, true);
	}
}
