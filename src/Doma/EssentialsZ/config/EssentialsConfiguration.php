<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\config;

use pocketmine\utils\Config;
use function dirname;
use function is_dir;
use function mkdir;

/**
 * Thin YAML configuration wrapper. Nested keys use dot notation
 * ("timestamps.login").
 */
class EssentialsConfiguration{

	protected Config $config;
	protected string $filePath;

	public function __construct(string $filePath){
		$this->filePath = $filePath;
		$this->load();
	}

	public function load() : void{
		$dir = dirname($this->filePath);
		if(!is_dir($dir)){
			@mkdir($dir, 0777, true);
		}
		$this->config = new Config($this->filePath, Config::YAML);
	}

	public function getFile() : string{
		return $this->filePath;
	}

	public function hasProperty(string $path) : bool{
		return $this->config->getNested($path) !== null;
	}

	public function getString(string $path, ?string $def = null) : ?string{
		$value = $this->config->getNested($path, $def);
		return $value === null ? null : (string) $value;
	}

	public function getBoolean(string $path, bool $def = false) : bool{
		return (bool) $this->config->getNested($path, $def);
	}

	public function getLong(string $path, int $def = 0) : int{
		return (int) $this->config->getNested($path, $def);
	}

	public function getDouble(string $path, float $def = 0.0) : float{
		return (float) $this->config->getNested($path, $def);
	}

	/**
	 * @param list<string> $def
	 * @return list<string>
	 */
	public function getList(string $path, array $def = []) : array{
		$value = $this->config->getNested($path, $def);
		return \is_array($value) ? \array_values($value) : $def;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getMap(string $path) : array{
		$value = $this->config->getNested($path);
		return \is_array($value) ? $value : [];
	}

	public function setProperty(string $path, mixed $value) : void{
		$this->config->setNested($path, $value);
	}

	public function removeProperty(string $path) : void{
		$this->config->removeNested($path);
	}

	public function save() : void{
		$this->config->save();
	}
}
