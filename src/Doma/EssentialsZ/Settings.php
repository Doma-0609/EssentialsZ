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

use Doma\EssentialsZ\config\EssentialsConfiguration;
use Doma\EssentialsZ\session\UserStorageKey;
use pocketmine\utils\TextFormat;
use function in_array;
use function max;
use function strtolower;

class Settings implements ISettings{

	private EssentialsConfiguration $config;

	private string $locale = I18n::DEFAULT_LOCALE;
	private bool $perPlayerLocale = false;
	private bool $debug = false;
	private bool $verboseCommandUsages = true;
	private float $maxFlySpeed = 0.4;
	private float $maxWalkSpeed = 0.4;
	private float $scaleMin = 0.1;
	private float $scaleMax = 10.0;
	private bool $removeEffectsOnHeal = true;
	private bool $worldTimePermissions = false;
	private bool $autoVanish = true;
	private ?string $vanishFakeQuitMessage = null;
	private ?string $vanishFakeJoinMessage = null;
	private string $userStorageKey = UserStorageKey::NAME;
	private int $tpaAcceptCancellation = 120;
	private int $backDeathTimeLimit = 300;
	private int $maxHomes = 3;
	private int $randomTeleportMinRange = 250;
	private int $randomTeleportMaxRange = 5000;
	private int $randomTeleportAttempts = 10;

	public function __construct(private EssentialsZ $ess){
		$this->config = new EssentialsConfiguration($ess->getDataFolder() . "config.yml");
		$this->reloadConfig();
	}

	public function reloadConfig() : void{
		$this->config->load();

		$this->locale = $this->config->getString("locale", I18n::DEFAULT_LOCALE) ?? I18n::DEFAULT_LOCALE;
		$this->perPlayerLocale = $this->config->getBoolean("per-player-locale", false);
		$this->debug = $this->config->getBoolean("debug", false);
		$this->verboseCommandUsages = $this->config->getBoolean("verbose-command-usages", true);
		$this->maxFlySpeed = $this->config->getDouble("max-fly-speed", 0.4);
		$this->maxWalkSpeed = $this->config->getDouble("max-walk-speed", 0.4);
		$this->scaleMin = max(0.01, $this->config->getDouble("scale-min", 0.1));
		$this->scaleMax = max($this->scaleMin, $this->config->getDouble("scale-max", 10.0));
		$this->removeEffectsOnHeal = $this->config->getBoolean("remove-effects-on-heal", true);
		$this->worldTimePermissions = $this->config->getBoolean("world-time-permissions", false);
		$this->autoVanish = $this->config->getBoolean("auto-vanish", true);
		$this->vanishFakeQuitMessage = self::parseMessageFormat($this->config->getString("vanish-fake-quit-message", "none"));
		$this->vanishFakeJoinMessage = self::parseMessageFormat($this->config->getString("vanish-fake-join-message", "none"));

		$storageKey = strtolower($this->config->getString("user-storage-key", UserStorageKey::NAME) ?? UserStorageKey::NAME);
		$this->userStorageKey = in_array($storageKey, [UserStorageKey::NAME, UserStorageKey::UUID, UserStorageKey::XUID], true)
			? $storageKey
			: UserStorageKey::NAME;

		$this->tpaAcceptCancellation = (int) $this->config->getLong("tpa-accept-cancellation", 120);
		$this->backDeathTimeLimit = (int) $this->config->getLong("back-death-time-limit", 300);
		$this->maxHomes = (int) $this->config->getLong("max-homes", 3);
		$this->randomTeleportMinRange = (int) $this->config->getLong("random-teleport.min-range", 250);
		$this->randomTeleportMaxRange = (int) $this->config->getLong("random-teleport.max-range", 5000);
		$this->randomTeleportAttempts = (int) $this->config->getLong("random-teleport.attempts", 10);
	}

	public function getLocale() : string{
		return $this->locale;
	}

	public function isPerPlayerLocale() : bool{
		return $this->perPlayerLocale;
	}

	public function isDebug() : bool{
		return $this->debug;
	}

	public function setDebug(bool $debug) : void{
		$this->debug = $debug;
		$this->config->setProperty("debug", $debug);
		$this->config->save();
	}

	public function isVerboseCommandUsages() : bool{
		return $this->verboseCommandUsages;
	}

	public function getMaxFlySpeed() : float{
		return $this->maxFlySpeed;
	}

	public function getMaxWalkSpeed() : float{
		return $this->maxWalkSpeed;
	}

	public function getScaleMin() : float{
		return $this->scaleMin;
	}

	public function getScaleMax() : float{
		return $this->scaleMax;
	}

	public function isRemovingEffectsOnHeal() : bool{
		return $this->removeEffectsOnHeal;
	}

	public function isWorldTimePermissions() : bool{
		return $this->worldTimePermissions;
	}

	public function isAutoVanish() : bool{
		return $this->autoVanish;
	}

	public function getVanishFakeQuitMessage() : ?string{
		return $this->vanishFakeQuitMessage;
	}

	public function getVanishFakeJoinMessage() : ?string{
		return $this->vanishFakeJoinMessage;
	}

	/** "none"/empty maps to null, meaning the server's own format is used. */
	private static function parseMessageFormat(?string $format) : ?string{
		if($format === null || $format === "" || strtolower($format) === "none"){
			return null;
		}
		return TextFormat::colorize($format);
	}

	public function getUserStorageKey() : string{
		return $this->userStorageKey;
	}

	public function getTpaAcceptCancellation() : int{
		return $this->tpaAcceptCancellation;
	}

	public function getBackDeathTimeLimit() : int{
		return $this->backDeathTimeLimit;
	}

	public function getMaxHomes() : int{
		return $this->maxHomes;
	}

	public function getRandomTeleportMinRange() : int{
		return $this->randomTeleportMinRange;
	}

	public function getRandomTeleportMaxRange() : int{
		return $this->randomTeleportMaxRange;
	}

	public function getRandomTeleportAttempts() : int{
		return $this->randomTeleportAttempts;
	}
}
