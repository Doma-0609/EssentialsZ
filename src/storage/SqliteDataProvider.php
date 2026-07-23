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

use function class_exists;
use function dirname;
use function is_array;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;
use const JSON_THROW_ON_ERROR;
use const SQLITE3_TEXT;

/**
 * Stores each record as a row (id, JSON data) in an SQLite database.
 */
final class SqliteDataProvider implements DataProvider{

	private \SQLite3 $database;

	public function __construct(private string $file){}

	public function getName() : string{
		return "sqlite";
	}

	public function init() : void{
		if(!class_exists(\SQLite3::class)){
			throw new \RuntimeException("The sqlite3 PHP extension is not available");
		}
		$folder = dirname($this->file);
		if(!is_dir($folder)){
			@mkdir($folder, 0777, true);
		}
		$this->database = new \SQLite3($this->file);
		$this->database->busyTimeout(2000);
		$this->database->exec("CREATE TABLE IF NOT EXISTS players (id TEXT PRIMARY KEY, data TEXT NOT NULL)");
	}

	public function close() : void{
		if(isset($this->database)){
			$this->database->close();
		}
	}

	public function has(string $key) : bool{
		return $this->load($key) !== null;
	}

	public function load(string $key) : ?array{
		$statement = $this->database->prepare("SELECT data FROM players WHERE id = :id");
		$statement->bindValue(":id", $key, SQLITE3_TEXT);
		$result = $statement->execute();
		$row = $result === false ? false : $result->fetchArray(SQLITE3_NUM);
		$statement->close();
		if($row === false){
			return null;
		}
		$data = json_decode((string) $row[0], true);
		return is_array($data) ? $data : null;
	}

	public function save(string $key, array $data) : void{
		$statement = $this->database->prepare(
			"INSERT INTO players (id, data) VALUES (:id, :data)"
			. " ON CONFLICT(id) DO UPDATE SET data = :data"
		);
		$statement->bindValue(":id", $key, SQLITE3_TEXT);
		$statement->bindValue(":data", json_encode($data, JSON_THROW_ON_ERROR), SQLITE3_TEXT);
		$statement->execute();
		$statement->close();
	}

	public function delete(string $key) : void{
		$statement = $this->database->prepare("DELETE FROM players WHERE id = :id");
		$statement->bindValue(":id", $key, SQLITE3_TEXT);
		$statement->execute();
		$statement->close();
	}

	public function getKeys() : array{
		$result = $this->database->query("SELECT id FROM players");
		$keys = [];
		if($result !== false){
			while(($row = $result->fetchArray(SQLITE3_NUM)) !== false){
				$keys[] = (string) $row[0];
			}
		}
		return $keys;
	}

	public function getAll() : array{
		$result = $this->database->query("SELECT id, data FROM players");
		$records = [];
		if($result !== false){
			while(($row = $result->fetchArray(SQLITE3_NUM)) !== false){
				$data = json_decode((string) $row[1], true);
				if(is_array($data)){
					$records[(string) $row[0]] = $data;
				}
			}
		}
		return $records;
	}
}
