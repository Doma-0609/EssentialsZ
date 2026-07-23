<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\utils;

use Doma\EssentialsZ\I18n;
use function floor;
use function intdiv;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_ends_with;
use function strlen;
use function strtolower;
use function substr;

/**
 * Converts between world tick counts and the time descriptions players type:
 * tick counts ("4000ticks"), 24-hour ("17:30"), 12-hour ("4pm") and names
 * such as "day", "noon" or "midnight".
 */
final class TickFormat{

	public const TICKS_AT_MIDNIGHT = 18000;
	public const TICKS_PER_DAY = 24000;
	public const TICKS_PER_HOUR = 1000;

	/** @var array<string, int> */
	private const NAMES = [
		"sunrise" => 23000,
		"dawn" => 23000,
		"daystart" => 0,
		"day" => 0,
		"morning" => 1000,
		"midday" => 6000,
		"noon" => 6000,
		"afternoon" => 9000,
		"sunset" => 12000,
		"dusk" => 12000,
		"sundown" => 12000,
		"nightfall" => 12000,
		"nightstart" => 14000,
		"night" => 14000,
		"midnight" => 18000
	];

	private function __construct(){
	}

	/**
	 * @return int|null null when nothing understands the description
	 */
	public static function parse(string $description) : ?int{
		$description = (string) preg_replace("/[^A-Za-z0-9:]/", "", strtolower($description));

		return self::parseTicks($description)
			?? self::parse24($description)
			?? self::parse12($description)
			?? self::NAMES[$description]
			?? null;
	}

	private static function parseTicks(string $description) : ?int{
		if(preg_match("/^[0-9]+ti?c?k?s?$/", $description) !== 1){
			return null;
		}
		return (int) preg_replace("/[^0-9]/", "", $description) % self::TICKS_PER_DAY;
	}

	private static function parse24(string $description) : ?int{
		if(preg_match("/^[0-9]{2}[^0-9]?[0-9]{2}$/", $description) !== 1){
			return null;
		}
		$digits = (string) preg_replace("/[^0-9]/", "", $description);
		if(strlen($digits) !== 4){
			return null;
		}
		return self::fromHoursMinutes((int) substr($digits, 0, 2), (int) substr($digits, 2, 2));
	}

	private static function parse12(string $description) : ?int{
		if(preg_match("/^[0-9]{1,2}([^0-9]?[0-9]{2})?(pm|am)$/", $description) !== 1){
			return null;
		}
		$digits = (string) preg_replace("/[^0-9]/", "", $description);
		$hours = 0;
		$minutes = 0;
		switch(strlen($digits)){
			case 4:
				$hours = (int) substr($digits, 0, 2);
				$minutes = (int) substr($digits, 2, 2);
				break;
			case 3:
				$hours = (int) substr($digits, 0, 1);
				$minutes = (int) substr($digits, 1, 2);
				break;
			case 2:
			case 1:
				$hours = (int) $digits;
				break;
			default:
				return null;
		}
		if(str_ends_with($description, "pm") && $hours !== 12){
			$hours += 12;
		}elseif(str_ends_with($description, "am") && $hours === 12){
			$hours = 0;
		}
		return self::fromHoursMinutes($hours, $minutes);
	}

	public static function fromHoursMinutes(int $hours, int $minutes) : int{
		$ticks = self::TICKS_AT_MIDNIGHT
			+ $hours * self::TICKS_PER_HOUR
			+ (int) ($minutes / 60 * self::TICKS_PER_HOUR);
		return $ticks % self::TICKS_PER_DAY;
	}

	/**
	 * The three-way description shown to players: 24-hour, 12-hour and ticks.
	 */
	public static function format(int $ticks) : string{
		return I18n::tlLiteral("timeFormat", self::format24($ticks), self::format12($ticks), self::formatTicks($ticks));
	}

	public static function formatTicks(int $ticks) : string{
		return ($ticks % self::TICKS_PER_DAY) . " ticks";
	}

	public static function format24(int $ticks) : string{
		[$hours, $minutes] = self::toClock($ticks);
		return sprintf("%02d:%02d", $hours, $minutes);
	}

	public static function format12(int $ticks) : string{
		[$hours, $minutes] = self::toClock($ticks);
		$suffix = $hours < 12 ? "AM" : "PM";
		$hour12 = $hours % 12;
		return sprintf("%d:%02d %s", $hour12 === 0 ? 12 : $hour12, $minutes, $suffix);
	}

	/**
	 * @return array{int, int} hours, minutes of the in-game clock
	 */
	private static function toClock(int $ticks) : array{
		$offset = (($ticks % self::TICKS_PER_DAY) - self::TICKS_AT_MIDNIGHT + self::TICKS_PER_DAY) % self::TICKS_PER_DAY;
		$hours = intdiv($offset, self::TICKS_PER_HOUR);
		$minutes = (int) floor(($offset % self::TICKS_PER_HOUR) * 60 / self::TICKS_PER_HOUR);
		return [$hours, $minutes];
	}
}
