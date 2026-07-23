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

use function basename;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_array;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;
use function rename;
use function substr;
use function unlink;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * Stores each record as players/<key>.json.
 */
final class JsonDataProvider implements DataProvider{

	public function __construct(private string $folder){}

	public function getName() : string{
		return "json";
	}

	public function init() : void{
		if(!is_dir($this->folder)){
			@mkdir($this->folder, 0777, true);
		}
	}

	public function close() : void{
	}

	private function fileFor(string $key) : string{
		return $this->folder . DIRECTORY_SEPARATOR . $key . ".json";
	}

	public function has(string $key) : bool{
		return file_exists($this->fileFor($key));
	}

	public function load(string $key) : ?array{
		$file = $this->fileFor($key);
		if(!file_exists($file)){
			return null;
		}
		$raw = file_get_contents($file);
		if($raw === false || $raw === ""){
			return null;
		}
		$data = json_decode($raw, true);
		if(!is_array($data)){
			throw new \RuntimeException("Corrupted player record: " . $file);
		}
		return $data;
	}

	public function save(string $key, array $data) : void{
		// Atomic write: never leave a truncated record behind.
		$json = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
		$temp = $this->fileFor($key) . ".tmp";
		if(file_put_contents($temp, $json) !== false){
			@rename($temp, $this->fileFor($key));
		}
	}

	public function delete(string $key) : void{
		$file = $this->fileFor($key);
		if(file_exists($file)){
			@unlink($file);
		}
	}

	public function getKeys() : array{
		$keys = [];
		foreach(glob($this->folder . DIRECTORY_SEPARATOR . "*.json") ?: [] as $file){
			$keys[] = substr(basename($file), 0, -5);
		}
		return $keys;
	}

	public function getAll() : array{
		$records = [];
		foreach($this->getKeys() as $key){
			$record = $this->load($key);
			if($record !== null){
				$records[$key] = $record;
			}
		}
		return $records;
	}

	public function count() : int{
		return count($this->getKeys());
	}
}
