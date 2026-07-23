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

use function array_reverse;
use function count;
use function function_exists;
use function grapheme_strlen;
use function grapheme_substr;
use function implode;
use function preg_match;
use function preg_match_all;
use function strlen;
use function substr;
use const PREG_OFFSET_CAPTURE;

/**
 * Corrects right-to-left text for Bedrock clients, which render RTL runs
 * left-to-right.
 *
 * Every run of RTL characters is shaped (see PersianShaper), reversed, and
 * the runs are swapped end for end, so the client's own left-to-right
 * rendering ends up displaying the original text. Latin text, numbers and
 * punctuation between the runs keep their position.
 *
 * Obtain this through EssentialsZ::getRtl(), which returns null while the
 * module is disabled in the config - always null-check before use.
 */
final class RtlProcessor{

	private const RTL_RANGES =
		"\x{0590}-\x{05FF}" .   // Hebrew
		"\x{0600}-\x{06FF}" .   // Arabic and Persian
		"\x{0700}-\x{074F}" .   // Syriac
		"\x{0750}-\x{077F}" .   // Arabic Supplement
		"\x{0780}-\x{07BF}" .   // Thaana
		"\x{07C0}-\x{07FF}" .   // NKo
		"\x{0800}-\x{083F}" .   // Samaritan
		"\x{0840}-\x{085F}" .   // Mandaic
		"\x{08A0}-\x{08FF}" .   // Arabic Extended-A
		"\x{200C}-\x{200F}" .   // zero-width joiners and marks
		"\x{202A}-\x{202E}" .   // bidi controls
		"\x{FB1D}-\x{FB4F}" .   // Hebrew presentation forms
		"\x{FB50}-\x{FDFF}" .   // Arabic presentation forms A
		"\x{FE70}-\x{FEFF}";    // Arabic presentation forms B

	private const CHAR_PATTERN = '/[' . self::RTL_RANGES . ']/u';
	private const RUN_PATTERN = '/[' . self::RTL_RANGES . ']+(?:\s+[' . self::RTL_RANGES . ']+)*/u';

	public function __construct(private RtlSettings $settings){}

	public function getSettings() : RtlSettings{
		return $this->settings;
	}

	public function hasRtl(string $text) : bool{
		return $text !== "" && preg_match(self::CHAR_PATTERN, $text) === 1;
	}

	/**
	 * Returns $text ready to be sent to a Bedrock client. Text without RTL
	 * characters is returned unchanged.
	 */
	public function correct(string $text) : string{
		if(!$this->hasRtl($text)){
			return $text;
		}

		$matches = [];
		if(preg_match_all(self::RUN_PATTERN, $text, $matches, PREG_OFFSET_CAPTURE) === false || $matches[0] === []){
			return $text;
		}

		$runs = [];
		foreach($matches[0] as $match){
			$runs[] = ["text" => $match[0], "offset" => $match[1]];
		}

		$count = count($runs);
		$swapped = [];
		for($i = 0; $i < $count; $i++){
			$source = $runs[$count - 1 - $i]["text"];
			if($this->settings->shape){
				$source = PersianShaper::shape($source);
			}
			$swapped[$i] = self::reverse($source);
		}

		$result = "";
		$cursor = 0;
		for($i = 0; $i < $count; $i++){
			$offset = $runs[$i]["offset"];
			if($offset > $cursor){
				$result .= substr($text, $cursor, $offset - $cursor);
			}
			$result .= $swapped[$i];
			$cursor = $offset + strlen($runs[$i]["text"]);
		}
		if($cursor < strlen($text)){
			$result .= substr($text, $cursor);
		}
		return $result;
	}

	/**
	 * Grapheme-safe reversal, so combining marks stay on their letter.
	 */
	public static function reverse(string $text) : string{
		if($text === ""){
			return "";
		}
		if(function_exists("grapheme_strlen")){
			$length = grapheme_strlen($text);
			if($length !== false && $length > 0){
				$out = "";
				for($i = $length - 1; $i >= 0; $i--){
					$chunk = grapheme_substr($text, $i, 1);
					if($chunk !== false){
						$out .= $chunk;
					}
				}
				return $out;
			}
		}
		$chars = [];
		preg_match_all('/./us', $text, $chars);
		return implode("", array_reverse($chars[0]));
	}
}
