<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\rtl;

use function count;
use function implode;
use function mb_str_split;
use function preg_split;
use const PREG_SPLIT_DELIM_CAPTURE;

/**
 * Replaces Persian/Arabic letters with the presentation forms that render
 * joined on Bedrock. Every letter but the last of a word takes its connected
 * form; the final letter keeps its standalone shape.
 */
final class PersianShaper{

	/** Letter => connected (initial/medial) presentation form. */
	private const CONNECTED = [
		"ض" => "ﺿ", "ص" => "ﺻ", "ث" => "ﺛ", "ق" => "ﻗ", "ف" => "ﻓ",
		"غ" => "ﻏ", "ع" => "ﻋ", "ه" => "ﻫ", "خ" => "ﺧ", "ح" => "ﺣ",
		"ج" => "ﺟ", "چ" => "ﭼ", "پ" => "ﭘ", "ش" => "ﺷ", "س" => "ﺳ",
		"ی" => "ﯾ", "ب" => "ﺑ", "ل" => "ﻟ", "ت" => "ﺗ", "ن" => "ﻧ",
		"م" => "ﻣ", "ک" => "ﻛ", "گ" => "ﮔ", "ئ" => "ﺋ"
	];

	/** Letters that keep their own shape everywhere. */
	private const STANDALONE = ["ا", "ظ", "ط", "ز", "ر", "ذ", "د", "و", "ژ", "آ", "أ", "إ", "ؤ", "ة"];

	private function __construct(){
	}

	public static function isLetter(string $char) : bool{
		return isset(self::CONNECTED[$char]) || \in_array($char, self::STANDALONE, true);
	}

	/**
	 * Shapes every whitespace-separated word of $text, leaving the word order
	 * and the whitespace untouched.
	 */
	public static function shape(string $text) : string{
		$parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
		$out = [];
		foreach($parts as $part){
			$out[] = self::shapeWord($part);
		}
		return implode("", $out);
	}

	/**
	 * A word is only shaped when it starts with a Persian letter, so mixed
	 * tokens are left as typed.
	 */
	public static function shapeWord(string $word) : string{
		$chars = mb_str_split($word);
		$count = count($chars);
		if($count === 0 || !self::isLetter($chars[0])){
			return $word;
		}
		for($i = 0; $i < $count - 1; $i++){
			$chars[$i] = self::CONNECTED[$chars[$i]] ?? $chars[$i];
		}
		if($chars[$count - 1] === "ه"){
			$chars[$count - 1] = "ﻪ";
		}
		return implode("", $chars);
	}
}
