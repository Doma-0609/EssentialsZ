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

use function array_is_list;
use function mb_strtolower;

/**
 * A rectangular claim spanning the full height of one world. Coordinates are
 * inclusive block coordinates; the owner is a lowercased name and each invitee
 * is a lowercased name mapped to an access level.
 */
final class Land{

	public const LEVEL_BUILD = "build";
	public const LEVEL_CONTAINER = "container";

	/** @var array<string, string> lowercased name => access level */
	public array $invitees;

	/**
	 * @param array<string, string> $invitees
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $world,
		public readonly int $minX,
		public readonly int $minZ,
		public readonly int $maxX,
		public readonly int $maxZ,
		public readonly string $owner,
		public readonly float $price,
		array $invitees = []
	){
		$this->invitees = $invitees;
	}

	public function contains(string $world, int $x, int $z) : bool{
		return $world === $this->world
			&& $x >= $this->minX && $x <= $this->maxX
			&& $z >= $this->minZ && $z <= $this->maxZ;
	}

	public function overlaps(string $world, int $minX, int $minZ, int $maxX, int $maxZ) : bool{
		return $world === $this->world
			&& $this->minX <= $maxX && $this->maxX >= $minX
			&& $this->minZ <= $maxZ && $this->maxZ >= $minZ;
	}

	public function isOwner(string $name) : bool{
		return $this->owner === mb_strtolower($name);
	}

	/**
	 * @return string|null the invitee's access level, or null when not invited
	 */
	public function levelOf(string $name) : ?string{
		return $this->invitees[mb_strtolower($name)] ?? null;
	}

	public function width() : int{
		return $this->maxX - $this->minX + 1;
	}

	public function length() : int{
		return $this->maxZ - $this->minZ + 1;
	}

	public function area() : int{
		return $this->width() * $this->length();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray() : array{
		return [
			"id" => $this->id,
			"world" => $this->world,
			"minX" => $this->minX,
			"minZ" => $this->minZ,
			"maxX" => $this->maxX,
			"maxZ" => $this->maxZ,
			"owner" => $this->owner,
			"price" => $this->price,
			"invitees" => $this->invitees
		];
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromArray(array $data) : self{
		return new self(
			(int) $data["id"],
			(string) $data["world"],
			(int) $data["minX"],
			(int) $data["minZ"],
			(int) $data["maxX"],
			(int) $data["maxZ"],
			mb_strtolower((string) $data["owner"]),
			(float) ($data["price"] ?? 0),
			self::normalizeInvitees((array) ($data["invitees"] ?? []))
		);
	}

	/**
	 * Accepts both the current name => level map and the older flat list of
	 * names (which become full-build members).
	 *
	 * @param array<mixed> $raw
	 * @return array<string, string>
	 */
	private static function normalizeInvitees(array $raw) : array{
		if(array_is_list($raw)){
			$out = [];
			foreach($raw as $name){
				$out[mb_strtolower((string) $name)] = self::LEVEL_BUILD;
			}
			return $out;
		}
		$out = [];
		foreach($raw as $name => $level){
			$level = (string) $level;
			$out[mb_strtolower((string) $name)] = $level === self::LEVEL_CONTAINER ? self::LEVEL_CONTAINER : self::LEVEL_BUILD;
		}
		return $out;
	}
}
