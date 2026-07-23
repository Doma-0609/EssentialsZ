<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\storage;

use function array_key_exists;
use function array_values;
use function count;
use function explode;
use function is_array;

/**
 * One player's persistent record, loaded from and saved to a DataProvider.
 * Nested values use dot notation ("timestamps.login").
 */
final class PlayerData{

	/** @var array<string, mixed> */
	private array $data;

	public function __construct(
		private DataProvider $provider,
		private string $key
	){
		$this->data = $provider->load($key) ?? [];
	}

	public function getStorageKey() : string{
		return $this->key;
	}

	public function reload() : void{
		$this->data = $this->provider->load($this->key) ?? [];
	}

	public function save() : void{
		$this->provider->save($this->key, $this->data);
	}

	public function hasProperty(string $path) : bool{
		return $this->getNested($path) !== null;
	}

	public function getString(string $path, ?string $def = null) : ?string{
		$value = $this->getNested($path);
		return $value === null ? $def : (string) $value;
	}

	public function getBoolean(string $path, bool $def = false) : bool{
		$value = $this->getNested($path);
		return $value === null ? $def : (bool) $value;
	}

	public function getLong(string $path, int $def = 0) : int{
		$value = $this->getNested($path);
		return $value === null ? $def : (int) $value;
	}

	public function getDouble(string $path, float $def = 0.0) : float{
		$value = $this->getNested($path);
		return $value === null ? $def : (float) $value;
	}

	/**
	 * @param list<string> $def
	 * @return list<string>
	 */
	public function getList(string $path, array $def = []) : array{
		$value = $this->getNested($path);
		return is_array($value) ? array_values($value) : $def;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getMap(string $path) : array{
		$value = $this->getNested($path);
		return is_array($value) ? $value : [];
	}

	public function setProperty(string $path, mixed $value) : void{
		$keys = explode(".", $path);
		$cursor = &$this->data;
		$last = count($keys) - 1;
		foreach($keys as $i => $segment){
			if($i === $last){
				$cursor[$segment] = $value;
				return;
			}
			if(!isset($cursor[$segment]) || !is_array($cursor[$segment])){
				$cursor[$segment] = [];
			}
			$cursor = &$cursor[$segment];
		}
	}

	public function removeProperty(string $path) : void{
		$keys = explode(".", $path);
		$cursor = &$this->data;
		$last = count($keys) - 1;
		foreach($keys as $i => $segment){
			if($i === $last){
				unset($cursor[$segment]);
				return;
			}
			if(!isset($cursor[$segment]) || !is_array($cursor[$segment])){
				return;
			}
			$cursor = &$cursor[$segment];
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getAll() : array{
		return $this->data;
	}

	private function getNested(string $path) : mixed{
		$value = $this->data;
		foreach(explode(".", $path) as $segment){
			if(is_array($value) && array_key_exists($segment, $value)){
				$value = $value[$segment];
			}else{
				return null;
			}
		}
		return $value;
	}
}
