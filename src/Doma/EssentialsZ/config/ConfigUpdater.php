<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\config;

use function array_splice;
use function copy;
use function count;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_file;
use function ltrim;
use function preg_match;
use function rtrim;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;

/**
 * Adds config keys introduced by a plugin update to a server's existing
 * config.yml, keeping every value the admin already set.
 *
 * Driven by the "config-version" key: a file older than the bundled default is
 * filled in with any missing keys and stamped with the new version, so a later
 * start with a current file is left alone.
 *
 * The merge works on the raw text, not the parsed tree, so the admin's own
 * comments, spacing and key order all survive - only the lines for keys that
 * are genuinely missing are spliced in, each carrying the explanatory comment
 * that precedes it in the bundled default. The original file is backed up to
 * config.yml.bak first.
 */
final class ConfigUpdater{

	/** Spaces per indentation level in the config files. */
	private const INDENT = 2;

	private function __construct(){
	}

	/**
	 * @return int the number of keys added (0 when nothing changed)
	 */
	public static function update(string $defaultResource, string $liveFile) : int{
		if(!is_file($liveFile) || !is_file($defaultResource)){
			return 0; // a fresh install already has the current default
		}

		$defaultText = file_get_contents($defaultResource);
		$liveText = file_get_contents($liveFile);
		if($defaultText === false || $liveText === false){
			return 0;
		}

		$defaultPos = 0;
		$livePos = 0;
		$defaultBlocks = self::parseTop(self::splitLines($defaultText), $defaultPos);
		$liveBlocks = self::parseTop(self::splitLines($liveText), $livePos);

		$defaultVersion = self::versionOf($defaultBlocks);
		$liveVersion = self::versionOf($liveBlocks);
		if($liveVersion >= $defaultVersion){
			return 0;
		}

		$added = self::merge($defaultBlocks, $liveBlocks);
		self::stampVersion($liveBlocks, $defaultVersion);

		// Keep the admin's line-ending style so an unchanged file round-trips.
		$eol = str_contains($liveText, "\r\n") ? "\r\n" : "\n";

		@copy($liveFile, $liveFile . ".bak");
		file_put_contents($liveFile, self::render($liveBlocks, $eol));
		return $added;
	}

	/**
	 * @return list<string>
	 */
	private static function splitLines(string $text) : array{
		$normalized = str_replace(["\r\n", "\r"], "\n", $text);
		return explode("\n", $normalized);
	}

	/**
	 * Parses the whole file. Any trailing comments left dangling at the end of the
	 * file are kept as a final keyless block so they render back out.
	 *
	 * @param list<string> $lines
	 * @return list<array{key: ?string, lead: list<string>, line: ?string, children: list<mixed>}>
	 */
	private static function parseTop(array $lines, int &$pos) : array{
		[$blocks, $pending] = self::parse($lines, 0, $pos);
		if($pending !== []){
			$blocks[] = ["key" => null, "lead" => $pending, "line" => null, "children" => []];
		}
		return $blocks;
	}

	/**
	 * Reads the entries at one indentation level into blocks. Each block owns the
	 * comment/blank lines that precede its key, plus any deeper-indented lines as
	 * child blocks. Sequence items (- ...) and stray lines become keyless blocks
	 * so they are preserved verbatim but never treated as merge targets.
	 *
	 * Comments that follow the last entry at this level belong to whatever comes
	 * next (a sibling, or a key at a shallower level), so they are returned as a
	 * "pending" list rather than swallowed here - this keeps each explanatory
	 * comment glued to the key it documents even across indentation changes.
	 *
	 * @param list<string> $lines
	 * @return array{0: list<array{key: ?string, lead: list<string>, line: ?string, children: list<mixed>}>, 1: list<string>}
	 */
	private static function parse(array $lines, int $indent, int &$pos) : array{
		$count = count($lines);
		$blocks = [];
		$lead = [];
		while($pos < $count){
			$raw = rtrim($lines[$pos], "\r\n");
			$trimmedLeft = ltrim($raw, " ");

			if($trimmedLeft === "" || str_starts_with($trimmedLeft, "#")){
				$lead[] = $raw;
				$pos++;
				continue;
			}

			$lineIndent = strlen($raw) - strlen($trimmedLeft);
			if($lineIndent < $indent){
				break; // belongs to an ancestor level; $lead bubbles up with it
			}

			$key = self::keyName($trimmedLeft);
			$line = $raw;
			$pos++;
			[$children, $childPending] = self::parse($lines, $indent + self::INDENT, $pos);
			$blocks[] = ["key" => $key, "lead" => $lead, "line" => $line, "children" => $children];
			// Comments trailing this entry's children precede the next sibling.
			$lead = $childPending;
		}

		return [$blocks, $lead];
	}

	private static function keyName(string $trimmedLeft) : ?string{
		if(preg_match('/^([A-Za-z0-9_.-]+)\s*:/', $trimmedLeft, $m) === 1){
			return $m[1];
		}
		return null; // sequence item or unrecognised line
	}

	/**
	 * Splices every keyed block present in $default but missing from $live into
	 * $live, just after the last sibling they share, recursing into mappings.
	 *
	 * @param list<array{key: ?string, lead: list<string>, line: ?string, children: list<mixed>}> $default
	 * @param list<array{key: ?string, lead: list<string>, line: ?string, children: list<mixed>}> $live
	 */
	private static function merge(array $default, array &$live) : int{
		$added = 0;
		$anchor = -1;
		foreach($default as $block){
			if($block["key"] === null){
				continue;
			}
			$idx = self::indexOfKey($live, $block["key"]);
			if($idx !== -1){
				$anchor = $idx;
				if(self::hasKeyedChildren($block)){
					$added += self::merge($block["children"], $live[$idx]["children"]);
				}
			}else{
				$insertAt = $anchor + 1;
				array_splice($live, $insertAt, 0, [$block]);
				$anchor = $insertAt;
				$added += 1 + self::countKeyed($block["children"]);
			}
		}
		return $added;
	}

	/**
	 * @param list<array{key: ?string, ...}> $blocks
	 */
	private static function indexOfKey(array $blocks, string $key) : int{
		foreach($blocks as $i => $block){
			if($block["key"] === $key){
				return $i;
			}
		}
		return -1;
	}

	/**
	 * @param array{children: list<mixed>, ...} $block
	 */
	private static function hasKeyedChildren(array $block) : bool{
		foreach($block["children"] as $child){
			if($child["key"] !== null){
				return true;
			}
		}
		return false;
	}

	/**
	 * @param list<array{key: ?string, children: list<mixed>, ...}> $blocks
	 */
	private static function countKeyed(array $blocks) : int{
		$total = 0;
		foreach($blocks as $block){
			if($block["key"] !== null){
				$total += 1 + self::countKeyed($block["children"]);
			}
		}
		return $total;
	}

	/**
	 * @param list<array{key: ?string, line: ?string, ...}> $blocks
	 */
	private static function versionOf(array $blocks) : int{
		$idx = self::indexOfKey($blocks, "config-version");
		if($idx === -1){
			return 0;
		}
		if(preg_match('/:\s*(\d+)/', (string) $blocks[$idx]["line"], $m) === 1){
			return (int) $m[1];
		}
		return 0;
	}

	/**
	 * @param list<array{key: ?string, line: ?string, ...}> $blocks
	 */
	private static function stampVersion(array &$blocks, int $version) : void{
		$idx = self::indexOfKey($blocks, "config-version");
		if($idx !== -1){
			$blocks[$idx]["line"] = "config-version: " . $version;
		}
	}

	/**
	 * @param list<array{key: ?string, lead: list<string>, line: ?string, children: list<mixed>}> $blocks
	 */
	private static function render(array $blocks, string $eol) : string{
		$out = [];
		self::renderInto($blocks, $out);
		return rtrim(implode($eol, $out), "\r\n") . $eol;
	}

	/**
	 * @param list<array{key: ?string, lead: list<string>, line: ?string, children: list<mixed>}> $blocks
	 * @param list<string> $out
	 */
	private static function renderInto(array $blocks, array &$out) : void{
		foreach($blocks as $block){
			foreach($block["lead"] as $leadLine){
				$out[] = $leadLine;
			}
			if($block["line"] !== null){
				$out[] = $block["line"];
			}
			self::renderInto($block["children"], $out);
		}
	}
}
