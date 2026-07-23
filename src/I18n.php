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

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_values;
use function explode;
use function file_exists;
use function file_get_contents;
use function hexdec;
use function is_dir;
use function mb_chr;
use function mkdir;
use function preg_match;
use function preg_replace_callback;
use function str_contains;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function trim;

/**
 * Message translation service.
 *
 * Bundles are .properties files (messages_<locale>.properties) using
 * MessageFormat placeholders ({0}, {1}, ...). MiniMessage-style color
 * tags (<primary>, <secondary>, <dark_red>, ...) are converted to Bedrock
 * legacy formatting codes at bundle load time. Unknown tags (such as the
 * <command> placeholder inside usage strings) are kept literally.
 */
final class I18n{
	public const DEFAULT_LOCALE = "en";

	private const MESSAGES = "messages";

	/**
	 * MiniMessage-style tag -> Bedrock legacy code.
	 * <primary> renders gold, <secondary> renders red.
	 */
	private const TAG_MAP = [
		"primary" => TextFormat::GOLD,
		"secondary" => TextFormat::RED,
		"black" => TextFormat::BLACK,
		"dark_blue" => TextFormat::DARK_BLUE,
		"dark_green" => TextFormat::DARK_GREEN,
		"dark_aqua" => TextFormat::DARK_AQUA,
		"dark_red" => TextFormat::DARK_RED,
		"dark_purple" => TextFormat::DARK_PURPLE,
		"gold" => TextFormat::GOLD,
		"gray" => TextFormat::GRAY,
		"grey" => TextFormat::GRAY,
		"dark_gray" => TextFormat::DARK_GRAY,
		"dark_grey" => TextFormat::DARK_GRAY,
		"blue" => TextFormat::BLUE,
		"green" => TextFormat::GREEN,
		"aqua" => TextFormat::AQUA,
		"red" => TextFormat::RED,
		"light_purple" => TextFormat::LIGHT_PURPLE,
		"yellow" => TextFormat::YELLOW,
		"white" => TextFormat::WHITE,
		"bold" => TextFormat::BOLD,
		"b" => TextFormat::BOLD,
		"italic" => TextFormat::ITALIC,
		"i" => TextFormat::ITALIC,
		"em" => TextFormat::ITALIC,
		"obfuscated" => TextFormat::OBFUSCATED,
		"obf" => TextFormat::OBFUSCATED,
		"reset" => TextFormat::RESET,
		"r" => TextFormat::RESET
	];

	private static ?I18n $instance = null;

	private EssentialsZ $ess;

	private string $currentLocale = self::DEFAULT_LOCALE;

	/** @var array<string, string> */
	private array $defaultBundle = [];
	/** @var array<string, string> */
	private array $localeBundle = [];
	/**
	 * Lazily loaded bundles for per-player locales, keyed by locale string.
	 * A null entry marks a locale known to have no bundle.
	 *
	 * @var array<string, array<string, string>|null>
	 */
	private array $localeCache = [];

	public function __construct(EssentialsZ $ess){
		$this->ess = $ess;
	}

	public function onEnable() : void{
		self::$instance = $this;
		$this->defaultBundle = $this->loadBundle(self::DEFAULT_LOCALE) ?? [];
		$this->localeBundle = $this->defaultBundle;
	}

	public function onDisable() : void{
		if(self::$instance === $this){
			self::$instance = null;
		}
	}

	public function getCurrentLocale() : string{
		return $this->currentLocale;
	}

	/**
	 * Sets the active server locale, reloading bundles from disk.
	 */
	public function updateLocale(string $locale) : void{
		$locale = trim(strtolower($locale));
		if($locale === ""){
			$locale = self::DEFAULT_LOCALE;
		}
		$this->currentLocale = $locale;
		$this->localeCache = [];
		$this->defaultBundle = $this->loadBundle(self::DEFAULT_LOCALE) ?? [];
		$this->localeBundle = $this->loadBundle($locale) ?? $this->defaultBundle;
	}

	/**
	 * Translates a message in the active server locale.
	 */
	public function tl(string $tlKey, string|int|float ...$args) : string{
		$format = $this->localeBundle[$tlKey] ?? $this->defaultBundle[$tlKey] ?? $tlKey;
		return self::format($format, $args);
	}

	/**
	 * Translates a message for a specific player, honouring per-player-locale.
	 */
	public function tlPlayer(?Player $player, string $tlKey, string|int|float ...$args) : string{
		if($player !== null && $this->ess->getSettings()->isPerPlayerLocale()){
			$bundle = $this->getPlayerBundle($player->getLocale());
			if($bundle !== null && isset($bundle[$tlKey])){
				return self::format($bundle[$tlKey], $args);
			}
		}
		return $this->tl($tlKey, ...$args);
	}

	/**
	 * Static translation helper for contexts without service access
	 * (exceptions, early construction).
	 * Falls back to the raw key when the service is not enabled yet.
	 */
	public static function tlLiteral(string $tlKey, string|int|float ...$args) : string{
		if(self::$instance === null){
			return $tlKey;
		}
		return self::$instance->tl($tlKey, ...$args);
	}

	public function keyExists(string $tlKey) : bool{
		return isset($this->localeBundle[$tlKey]) || isset($this->defaultBundle[$tlKey]);
	}

	public static function keyExistsLiteral(string $tlKey) : bool{
		return self::$instance !== null && self::$instance->keyExists($tlKey);
	}

	/**
	 * Applies MessageFormat-style placeholders: {0}, {1}, ... and '' -> '.
	 *
	 * @param array<int, string|int|float> $args
	 */
	public static function format(string $format, array $args) : string{
		$args = array_values($args);
		foreach($args as $i => $arg){
			$format = str_replace("{" . $i . "}", (string) $arg, $format);
		}
		return str_replace("''", "'", $format);
	}

	/**
	 * Resolves a bundle for a raw client locale like "fa_IR":
	 * tries messages_fa_ir, then messages_fa. Results (incl. misses) cached.
	 *
	 * @return array<string, string>|null
	 */
	private function getPlayerBundle(string $clientLocale) : ?array{
		$locale = strtolower(trim($clientLocale));
		if($locale === "" || $locale === $this->currentLocale){
			return null;
		}
		if(isset($this->localeCache[$locale]) || \array_key_exists($locale, $this->localeCache)){
			return $this->localeCache[$locale];
		}
		$bundle = $this->loadBundle($locale);
		if($bundle === null && str_contains($locale, "_")){
			$bundle = $this->loadBundle(explode("_", $locale, 2)[0]);
		}
		return $this->localeCache[$locale] = $bundle;
	}

	/**
	 * Loads messages_<locale>.properties. Keys from the bundled resource are
	 * the base, keys from the file on disk override them. This keeps admin
	 * customisations while still filling in keys added by a plugin update
	 * that the on-disk file predates.
	 *
	 * @return array<string, string>|null
	 */
	private function loadBundle(string $locale) : ?array{
		$fileName = self::MESSAGES . "_" . $locale . ".properties";
		$dataDir = $this->ess->getDataFolder() . self::MESSAGES;
		if(!is_dir($dataDir)){
			@mkdir($dataDir, 0777, true);
		}

		$resource = self::parsePropertiesFile($this->ess->getResourcePath(self::MESSAGES . "/" . $fileName));
		$onDisk = self::parsePropertiesFile($dataDir . DIRECTORY_SEPARATOR . $fileName);
		if($resource === null && $onDisk === null){
			return null;
		}

		$bundle = [];
		foreach(($resource ?? []) + ($onDisk ?? []) as $key => $value){
			$value = $onDisk[$key] ?? $resource[$key] ?? $value;
			$bundle[$key] = self::replaceTags($value);
		}
		return $bundle;
	}

	/**
	 * @return array<string, string>|null null when the file does not exist
	 */
	private static function parsePropertiesFile(string $path) : ?array{
		if(!file_exists($path)){
			return null;
		}
		$contents = file_get_contents($path);
		return $contents === false ? null : self::parseProperties($contents);
	}

	/**
	 * Minimal .properties parser: comments (#, !), key=value / key: value,
	 * backslash escapes (\:, \=, \n, \t, \\, \uXXXX).
	 *
	 * @return array<string, string>
	 */
	public static function parseProperties(string $contents) : array{
		$result = [];
		foreach(explode("\n", str_replace(["\r\n", "\r"], "\n", $contents)) as $line){
			$line = trim($line);
			if($line === "" || $line[0] === "#" || $line[0] === "!"){
				continue;
			}
			$separator = null;
			$len = strlen($line);
			for($i = 0; $i < $len; $i++){
				$char = $line[$i];
				if($char === "\\"){
					$i++; // skip escaped character
					continue;
				}
				if($char === "=" || $char === ":"){
					$separator = $i;
					break;
				}
			}
			if($separator === null){
				continue;
			}
			$key = trim(self::unescape(substr($line, 0, $separator)));
			$value = self::unescape(ltrim(substr($line, $separator + 1)));
			if($key !== ""){
				$result[$key] = $value;
			}
		}
		return $result;
	}

	private static function unescape(string $raw) : string{
		$raw = preg_replace_callback("/\\\\u([0-9a-fA-F]{4})/", static function(array $matches) : string{
			return mb_chr((int) hexdec($matches[1]), "UTF-8");
		}, $raw) ?? $raw;

		$out = "";
		$len = strlen($raw);
		for($i = 0; $i < $len; $i++){
			$char = $raw[$i];
			if($char === "\\" && $i + 1 < $len){
				$next = $raw[++$i];
				$out .= match($next){
					"n" => "\n",
					"t" => "\t",
					default => $next
				};
			}else{
				$out .= $char;
			}
		}
		return $out;
	}

	/**
	 * Converts MiniMessage-style tags to legacy formatting codes.
	 * Closing tags are dropped (legacy codes have no scopes); unknown tags
	 * (e.g. <command>, <survival|creative|...>) are kept literally.
	 */
	public static function replaceTags(string $message) : string{
		return preg_replace_callback("/<(\\/?)([a-zA-Z_][a-zA-Z0-9_]*)>/", static function(array $matches) : string{
			$tag = strtolower($matches[2]);
			if(!isset(self::TAG_MAP[$tag])){
				return $matches[0]; // unknown tag: keep literally
			}
			return $matches[1] === "/" ? "" : self::TAG_MAP[$tag];
		}, $message) ?? $message;
	}
}
