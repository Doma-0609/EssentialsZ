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
use function count;
use function implode;
use function intdiv;

final class DateUtil{

	private function __construct(){
	}

	public static function formatDateDiff(int $futureMillis) : string{
		$now = new \DateTimeImmutable();
		$to = (new \DateTimeImmutable())->setTimestamp(intdiv($futureMillis, 1000));
		if($to <= $now){
			return I18n::tlLiteral("now");
		}
		$diff = $now->diff($to);

		$units = [
			[$diff->y, "year", "years"],
			[$diff->m, "month", "months"],
			[$diff->d, "day", "days"],
			[$diff->h, "hour", "hours"],
			[$diff->i, "minute", "minutes"],
			[$diff->s, "second", "seconds"]
		];
		$parts = [];
		foreach($units as [$value, $singular, $plural]){
			if(count($parts) > 2){
				break;
			}
			if($value > 0){
				$parts[] = $value . " " . I18n::tlLiteral($value > 1 ? $plural : $singular);
			}
		}
		return $parts === [] ? I18n::tlLiteral("now") : implode(" ", $parts);
	}
}
