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

/**
 * Backing store for player data. Each player is one record - an associative
 * array holding everything that needs saving (homes, money, kit cooldowns,
 * timestamps, ...) - keyed by the resolved user-storage-key. JSON, SQLite and
 * MySQL backends ship with the plugin.
 */
interface DataProvider{

	public function getName() : string;

	/**
	 * @throws \RuntimeException when the backend is unavailable
	 */
	public function init() : void;

	public function close() : void;

	public function has(string $key) : bool;

	/**
	 * @return array<string, mixed>|null null when the record does not exist
	 */
	public function load(string $key) : ?array;

	/**
	 * @param array<string, mixed> $data
	 */
	public function save(string $key, array $data) : void;

	public function delete(string $key) : void;

	/**
	 * @return list<string>
	 */
	public function getKeys() : array;

	/**
	 * @return array<string, array<string, mixed>> every record, keyed by storage key
	 */
	public function getAll() : array;
}
