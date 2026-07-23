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
use function is_array;
use function json_decode;
use function json_encode;
use function preg_match;
use const JSON_THROW_ON_ERROR;

/**
 * Stores each record as a row (id, JSON data) in a MySQL database.
 */
final class MysqlDataProvider implements DataProvider{

	private \mysqli $connection;

	public function __construct(private StorageSettings $settings){}

	public function getName() : string{
		return "mysql";
	}

	public function init() : void{
		if(!class_exists(\mysqli::class)){
			throw new \RuntimeException("The mysqli PHP extension is not available");
		}
		if(preg_match("/^[A-Za-z0-9_]+$/", $this->settings->mysqlTable) !== 1){
			throw new \RuntimeException("Invalid MySQL table name: " . $this->settings->mysqlTable);
		}

		\mysqli_report(\MYSQLI_REPORT_ERROR | \MYSQLI_REPORT_STRICT);
		try{
			$this->connection = new \mysqli(
				$this->settings->mysqlHost,
				$this->settings->mysqlUsername,
				$this->settings->mysqlPassword,
				$this->settings->mysqlDatabase,
				$this->settings->mysqlPort
			);
		}catch(\mysqli_sql_exception $e){
			throw new \RuntimeException("Could not connect to MySQL: " . $e->getMessage(), 0, $e);
		}
		$this->connection->set_charset("utf8mb4");
		$this->connection->query(
			"CREATE TABLE IF NOT EXISTS `" . $this->settings->mysqlTable . "` ("
			. "id VARCHAR(64) NOT NULL PRIMARY KEY,"
			. "data LONGTEXT NOT NULL"
			. ")"
		);
	}

	public function close() : void{
		if(isset($this->connection)){
			$this->connection->close();
		}
	}

	private function table() : string{
		return "`" . $this->settings->mysqlTable . "`";
	}

	public function has(string $key) : bool{
		return $this->load($key) !== null;
	}

	public function load(string $key) : ?array{
		$statement = $this->connection->prepare("SELECT data FROM " . $this->table() . " WHERE id = ?");
		$statement->bind_param("s", $key);
		$statement->execute();
		$result = $statement->get_result();
		$row = $result === false ? null : $result->fetch_row();
		$statement->close();
		if($row === null){
			return null;
		}
		$data = json_decode((string) $row[0], true);
		return is_array($data) ? $data : null;
	}

	public function save(string $key, array $data) : void{
		$json = json_encode($data, JSON_THROW_ON_ERROR);
		$statement = $this->connection->prepare(
			"INSERT INTO " . $this->table() . " (id, data) VALUES (?, ?)"
			. " ON DUPLICATE KEY UPDATE data = VALUES(data)"
		);
		$statement->bind_param("ss", $key, $json);
		$statement->execute();
		$statement->close();
	}

	public function delete(string $key) : void{
		$statement = $this->connection->prepare("DELETE FROM " . $this->table() . " WHERE id = ?");
		$statement->bind_param("s", $key);
		$statement->execute();
		$statement->close();
	}

	public function getKeys() : array{
		$result = $this->connection->query("SELECT id FROM " . $this->table());
		$keys = [];
		if($result !== false){
			while(($row = $result->fetch_row()) !== null){
				$keys[] = (string) $row[0];
			}
		}
		return $keys;
	}

	public function getAll() : array{
		$result = $this->connection->query("SELECT id, data FROM " . $this->table());
		$records = [];
		if($result !== false){
			while(($row = $result->fetch_row()) !== null){
				$data = json_decode((string) $row[1], true);
				if(is_array($data)){
					$records[(string) $row[0]] = $data;
				}
			}
		}
		return $records;
	}
}
