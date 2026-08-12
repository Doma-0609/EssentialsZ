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

use Doma\EssentialsZ\IEssentials;
use pocketmine\player\Player;
use function array_values;
use function count;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function is_array;
use function is_file;
use function json_decode;
use function json_encode;
use function max;
use function mb_strtolower;
use function min;
use function rename;
use function str_contains;
use function strtolower;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * Stores and protects land claims.
 *
 * Claims are indexed by the world chunks they cover, so a per-block lookup only
 * scans the handful of claims touching that chunk instead of every claim.
 *
 * Obtain this through EssentialsZ::getLand(), which returns null when the land
 * module is disabled in the config - always null-check before use.
 */
final class LandManager{

	public const RESULT_OK = 0;
	public const RESULT_OVERLAP = 1;
	public const RESULT_TOO_SMALL = 2;
	public const RESULT_TOO_BIG = 3;
	public const RESULT_LIMIT = 4;
	public const RESULT_WORLD_DISALLOWED = 5;
	public const RESULT_NOT_ENOUGH_MONEY = 6;
	public const RESULT_NO_ECONOMY = 7;

	/** @var array<int, Land> */
	private array $lands = [];
	/** @var array<string, list<int>> chunk key => land ids */
	private array $chunkIndex = [];
	/** @var array<string, array{world: string, first?: array{int, int}, second?: array{int, int}}> lowercased name => selection */
	private array $selections = [];
	private int $nextId = 0;
	private string $file;

	public function __construct(
		private IEssentials $ess,
		private LandSettings $settings
	){
		$this->file = $ess->getDataFolder() . "lands.json";
		$this->load();
	}

	public function getSettings() : LandSettings{
		return $this->settings;
	}

	private static function chunkKey(string $world, int $chunkX, int $chunkZ) : string{
		return $world . ";" . $chunkX . ";" . $chunkZ;
	}

	private function index(Land $land) : void{
		$this->lands[$land->id] = $land;
		for($cx = $land->minX >> 4; $cx <= $land->maxX >> 4; $cx++){
			for($cz = $land->minZ >> 4; $cz <= $land->maxZ >> 4; $cz++){
				$this->chunkIndex[self::chunkKey($land->world, $cx, $cz)][] = $land->id;
			}
		}
	}

	private function unindex(Land $land) : void{
		unset($this->lands[$land->id]);
		for($cx = $land->minX >> 4; $cx <= $land->maxX >> 4; $cx++){
			for($cz = $land->minZ >> 4; $cz <= $land->maxZ >> 4; $cz++){
				$key = self::chunkKey($land->world, $cx, $cz);
				if(isset($this->chunkIndex[$key])){
					$this->chunkIndex[$key] = array_values(\array_filter($this->chunkIndex[$key], static fn(int $id) => $id !== $land->id));
					if($this->chunkIndex[$key] === []){
						unset($this->chunkIndex[$key]);
					}
				}
			}
		}
	}

	public function getLandAt(string $world, int $x, int $z) : ?Land{
		$key = self::chunkKey(strtolower($world), $x >> 4, $z >> 4);
		foreach($this->chunkIndex[$key] ?? [] as $id){
			$land = $this->lands[$id];
			if($land->contains($land->world, $x, $z)){
				return $land;
			}
		}
		return null;
	}

	public function getLandById(int $id) : ?Land{
		return $this->lands[$id] ?? null;
	}

	/**
	 * @return list<Land>
	 */
	public function getLands() : array{
		return array_values($this->lands);
	}

	/**
	 * @return list<Land>
	 */
	public function getLandsOfOwner(string $owner) : array{
		$owner = mb_strtolower($owner);
		$result = [];
		foreach($this->lands as $land){
			if($land->owner === $owner){
				$result[] = $land;
			}
		}
		return $result;
	}

	/**
	 * @return list<Land> claims whose owner name contains the keyword
	 */
	public function getLandsByKeyword(string $keyword) : array{
		$keyword = mb_strtolower($keyword);
		$result = [];
		foreach($this->lands as $land){
			if($keyword === "" || \str_contains($land->owner, $keyword)){
				$result[] = $land;
			}
		}
		return $result;
	}

	public function setOwner(Land $land, string $owner) : Land{
		$this->unindex($land);
		$moved = new Land($land->id, $land->world, $land->minX, $land->minZ, $land->maxX, $land->maxZ, mb_strtolower($owner), $land->price, []);
		$this->index($moved);
		$this->save();
		return $moved;
	}

	public function setSelection(string $name, string $world, bool $first, int $x, int $z) : void{
		$name = mb_strtolower($name);
		$selection = $this->selections[$name] ?? null;
		if($selection === null || $selection["world"] !== $world){
			$selection = ["world" => $world];
		}
		$selection[$first ? "first" : "second"] = [$x, $z];
		$this->selections[$name] = $selection;
	}

	/**
	 * @return array{world: string, first: array{int, int}, second: array{int, int}}|null
	 *         null unless both corners are set in the same world
	 */
	public function getSelection(string $name) : ?array{
		$selection = $this->selections[mb_strtolower($name)] ?? null;
		if($selection === null || !isset($selection["first"], $selection["second"])){
			return null;
		}
		return $selection;
	}

	public function clearSelection(string $name) : void{
		unset($this->selections[mb_strtolower($name)]);
	}

	private function overlaps(string $world, int $minX, int $minZ, int $maxX, int $maxZ) : ?Land{
		$seen = [];
		for($cx = $minX >> 4; $cx <= $maxX >> 4; $cx++){
			for($cz = $minZ >> 4; $cz <= $maxZ >> 4; $cz++){
				foreach($this->chunkIndex[self::chunkKey($world, $cx, $cz)] ?? [] as $id){
					if(isset($seen[$id])){
						continue;
					}
					$seen[$id] = true;
					if($this->lands[$id]->overlaps($world, $minX, $minZ, $maxX, $maxZ)){
						return $this->lands[$id];
					}
				}
			}
		}
		return null;
	}

	public function priceOf(int $minX, int $minZ, int $maxX, int $maxZ) : float{
		$width = $maxX - $minX + 1;
		$length = $maxZ - $minZ + 1;
		return $width * $length * $this->settings->pricePerBlock;
	}

	/**
	 * True when the player may break, place or trample at the position: their
	 * own claim, a claim they are a build member of, an unclaimed spot outside a
	 * protected world, or with the bypass permission.
	 */
	public function canBuild(Player $player, string $world, int $x, int $z) : bool{
		$land = $this->gate($player, $world, $x, $z);
		if(!($land instanceof Land)){
			return $land;
		}
		return $land->levelOf($player->getName()) === Land::LEVEL_BUILD;
	}

	/**
	 * Like canBuild(), but container-level members may also interact
	 * (open chests, use doors) without being able to break or place.
	 */
	public function canInteract(Player $player, string $world, int $x, int $z) : bool{
		$land = $this->gate($player, $world, $x, $z);
		if(!($land instanceof Land)){
			return $land;
		}
		$level = $land->levelOf($player->getName());
		return $level === Land::LEVEL_BUILD || $level === Land::LEVEL_CONTAINER;
	}

	/**
	 * Resolves the shared access rules: returns a definitive bool for the
	 * non-check world, bypass, unclaimed and owner cases, or the Land whose
	 * invitee level the caller must still check.
	 */
	private function gate(Player $player, string $world, int $x, int $z) : bool|Land{
		$world = strtolower($world);
		if(in_array($world, $this->settings->nonCheckWorlds, true)){
			return true;
		}
		if($player->hasPermission("essentialsz.land.bypass")){
			return true;
		}
		$land = $this->getLandAt($world, $x, $z);
		if($land === null){
			return !in_array($world, $this->settings->protectedWorlds, true);
		}
		if($land->isOwner($player->getName())){
			return true;
		}
		return $land;
	}

	/**
	 * Creates and (when the price is positive) charges for a claim.
	 *
	 * @return int one of the RESULT_* constants
	 */
	public function buy(Player $player, string $world, int $x1, int $z1, int $x2, int $z2) : int{
		$world = strtolower($world);
		if(in_array($world, $this->settings->buyDisallowedWorlds, true)){
			return self::RESULT_WORLD_DISALLOWED;
		}
		$minX = min($x1, $x2);
		$maxX = max($x1, $x2);
		$minZ = min($z1, $z2);
		$maxZ = max($z1, $z2);

		$width = $maxX - $minX + 1;
		$length = $maxZ - $minZ + 1;
		if($width < $this->settings->minSize || $length < $this->settings->minSize){
			return self::RESULT_TOO_SMALL;
		}
		if($width > $this->settings->maxSize || $length > $this->settings->maxSize){
			return self::RESULT_TOO_BIG;
		}
		if($this->overlaps($world, $minX, $minZ, $maxX, $maxZ) !== null){
			return self::RESULT_OVERLAP;
		}
		if($this->settings->maxPerPlayer > 0
			&& count($this->getLandsOfOwner($player->getName())) >= $this->settings->maxPerPlayer
			&& !$player->hasPermission("essentialsz.land.limit.bypass")){
			return self::RESULT_LIMIT;
		}

		$price = $this->priceOf($minX, $minZ, $maxX, $maxZ);
		if($price > 0){
			$economy = $this->ess->getEconomy();
			if($economy === null){
				return self::RESULT_NO_ECONOMY;
			}
			if(!$economy->hasBalance($player->getName(), $price) || !$economy->subtractBalance($player->getName(), $price)){
				return self::RESULT_NOT_ENOUGH_MONEY;
			}
		}

		$land = new Land($this->nextId++, $world, $minX, $minZ, $maxX, $maxZ, mb_strtolower($player->getName()), $price);
		$this->index($land);
		$this->save();
		return self::RESULT_OK;
	}

	/**
	 * Removes a claim, refunding the owner when a refund ratio applies.
	 */
	public function remove(Land $land) : void{
		$this->unindex($land);
		$this->save();
	}

	/**
	 * Sets a member's access level, or removes them when $level is null.
	 */
	public function setInvited(Land $land, string $name, ?string $level) : void{
		$name = mb_strtolower($name);
		if($level === null){
			unset($land->invitees[$name]);
		}else{
			$land->invitees[$name] = $level === Land::LEVEL_CONTAINER ? Land::LEVEL_CONTAINER : Land::LEVEL_BUILD;
		}
		$this->save();
	}

	private function load() : void{
		if(!is_file($this->file)){
			return;
		}
		$raw = file_get_contents($this->file);
		if($raw === false || $raw === ""){
			return;
		}
		$data = json_decode($raw, true);
		if(!is_array($data)){
			return;
		}
		foreach($data as $entry){
			if(!is_array($entry)){
				continue;
			}
			$land = Land::fromArray($entry);
			$this->index($land);
			$this->nextId = max($this->nextId, $land->id + 1);
		}
	}

	public function save() : void{
		$out = [];
		foreach($this->lands as $land){
			$out[] = $land->toArray();
		}
		$json = json_encode($out, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
		$temp = $this->file . ".tmp";
		if(file_put_contents($temp, $json) !== false){
			@rename($temp, $this->file);
		}
	}
}
