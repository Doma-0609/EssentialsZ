<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ;

interface ISettings{

	public function getLocale() : string;

	public function isPerPlayerLocale() : bool;

	public function isDebug() : bool;

	public function setDebug(bool $debug) : void;

	public function isVerboseCommandUsages() : bool;

	public function getMaxFlySpeed() : float;

	public function getMaxWalkSpeed() : float;

	public function isRemovingEffectsOnHeal() : bool;

	public function isWorldTimePermissions() : bool;

	public function getVanishFakeQuitMessage() : ?string;

	public function getVanishFakeJoinMessage() : ?string;

	public function getUserStorageKey() : string;

	public function getTpaAcceptCancellation() : int;

	public function getBackDeathTimeLimit() : int;

	public function getMaxHomes() : int;

	public function getRandomTeleportMinRange() : int;

	public function getRandomTeleportMaxRange() : int;

	public function getRandomTeleportAttempts() : int;

	public function reloadConfig() : void;
}
