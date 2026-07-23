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

use Doma\EssentialsZ\config\EssentialsConfiguration;
use function in_array;
use function strtolower;

final class StorageSettings{

	public const PROVIDER_JSON = "json";
	public const PROVIDER_SQLITE = "sqlite";
	public const PROVIDER_MYSQL = "mysql";

	public function __construct(
		public readonly string $provider,
		public readonly string $mysqlHost,
		public readonly int $mysqlPort,
		public readonly string $mysqlUsername,
		public readonly string $mysqlPassword,
		public readonly string $mysqlDatabase,
		public readonly string $mysqlTable
	){}

	public function createProvider(string $dataFolder) : DataProvider{
		return match($this->provider){
			self::PROVIDER_SQLITE => new SqliteDataProvider($dataFolder . "storage" . DIRECTORY_SEPARATOR . "players.sqlite3"),
			self::PROVIDER_MYSQL => new MysqlDataProvider($this),
			default => new JsonDataProvider($dataFolder . "players")
		};
	}

	public static function fromConfig(EssentialsConfiguration $config) : self{
		$provider = strtolower($config->getString("storage.provider", self::PROVIDER_JSON) ?? self::PROVIDER_JSON);
		if(!in_array($provider, [self::PROVIDER_JSON, self::PROVIDER_SQLITE, self::PROVIDER_MYSQL], true)){
			$provider = self::PROVIDER_JSON;
		}
		return new self(
			$provider,
			$config->getString("storage.mysql.host", "127.0.0.1") ?? "127.0.0.1",
			(int) $config->getLong("storage.mysql.port", 3306),
			$config->getString("storage.mysql.username", "root") ?? "root",
			$config->getString("storage.mysql.password", "") ?? "",
			$config->getString("storage.mysql.database", "essentialsz") ?? "essentialsz",
			$config->getString("storage.mysql.table", "players") ?? "players"
		);
	}
}
