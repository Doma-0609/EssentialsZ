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
use pocketmine\utils\Config;
use function basename;
use function count;
use function file_exists;
use function glob;
use function is_dir;
use function ksort;
use function mb_strtolower;
use function mkdir;
use function preg_match;
use function preg_replace;
use function substr;
use function trim;
use function unlink;
use Doma\EssentialsZ\IEssentials;

final class Warps{

	/** @var array<string, Warp> */
	private array $warps = [];

	public function __construct(private IEssentials $ess){
		$this->reloadConfig();
	}

	private function getFolder() : string{
		$folder = $this->ess->getDataFolder() . "warps";
		if(!is_dir($folder)){
			@mkdir($folder, 0777, true);
		}
		return $folder;
	}

	public function reloadConfig() : void{
		$this->warps = [];
		foreach(glob($this->getFolder() . DIRECTORY_SEPARATOR . "*.yml") ?: [] as $file){
			$config = new Config($file, Config::YAML);
			$name = (string) $config->get("name", substr(basename($file), 0, -4));
			if($name === ""){
				continue;
			}
			$this->warps[mb_strtolower($name)] = new Warp(
				$name,
				(string) $config->get("display-name", $name),
				(int) $config->get("icon-type", Warp::ICON_NONE),
				(string) $config->get("icon", ""),
				[
					"world" => $config->get("world"),
					"x" => $config->get("x"),
					"y" => $config->get("y"),
					"z" => $config->get("z"),
					"yaw" => $config->get("yaw", 0.0),
					"pitch" => $config->get("pitch", 0.0)
				]
			);
		}
		ksort($this->warps);
	}

	public static function isValidName(string $name) : bool{
		return preg_match("/^[A-Za-z0-9 _-]{1,32}$/", $name) === 1;
	}

	private function getFile(string $name) : string{
		$safe = (string) preg_replace("/[^a-z0-9_-]/", "_", mb_strtolower(trim($name)));
		return $this->getFolder() . DIRECTORY_SEPARATOR . $safe . ".yml";
	}

	/**
	 * @return list<string>
	 */
	public function getList() : array{
		$names = [];
		foreach($this->warps as $warp){
			$names[] = $warp->name;
		}
		return $names;
	}

	/**
	 * @return list<Warp>
	 */
	public function getAll() : array{
		return \array_values($this->warps);
	}

	public function getWarp(string $name) : ?Warp{
		return $this->warps[mb_strtolower(trim($name))] ?? null;
	}

	public function setWarp(string $name, string $displayName, int $iconType, string $icon, Location $location) : void{
		$config = new Config($this->getFile($name), Config::YAML);
		$config->setAll([
			"name" => $name,
			"display-name" => $displayName,
			"icon-type" => $iconType,
			"icon" => $icon
		] + LocationUtil::toMap($location));
		$config->save();

		$this->warps[mb_strtolower($name)] = new Warp($name, $displayName, $iconType, $icon, LocationUtil::toMap($location));
		ksort($this->warps);
	}

	public function removeWarp(string $name) : bool{
		$warp = $this->getWarp($name);
		if($warp === null){
			return false;
		}
		unset($this->warps[mb_strtolower($warp->name)]);
		$file = $this->getFile($warp->name);
		if(file_exists($file)){
			@unlink($file);
		}
		return true;
	}

	public function count() : int{
		return count($this->warps);
	}
}
