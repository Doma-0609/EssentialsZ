<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\kit;

use Doma\EssentialsZ\commands\TranslatableException;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\utils\Config;
use function array_values;
use function base64_decode;
use function base64_encode;
use function basename;
use function count;
use function file_exists;
use function glob;
use function is_dir;
use function is_numeric;
use function is_string;
use function ksort;
use function max;
use function mb_strtolower;
use function mkdir;
use function preg_match;
use function preg_replace;
use function preg_split;
use function str_replace;
use function substr;
use function trim;
use function unlink;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\session\User;

final class Kits{

	/** @var array<string, Kit> */
	private array $kits = [];

	public function __construct(private IEssentials $ess){
		$this->reloadConfig();
	}

	private function getFolder() : string{
		$folder = $this->ess->getDataFolder() . "kits";
		if(!is_dir($folder)){
			@mkdir($folder, 0777, true);
		}
		return $folder;
	}

	public function reloadConfig() : void{
		$this->kits = [];
		foreach(glob($this->getFolder() . DIRECTORY_SEPARATOR . "*.yml") ?: [] as $file){
			$config = new Config($file, Config::YAML);
			$name = (string) $config->get("name", substr(basename($file), 0, -4));
			if($name === ""){
				continue;
			}
			$this->kits[mb_strtolower($name)] = new Kit(
				$name,
				(string) $config->get("display-name", $name),
				(float) $config->get("delay", 0),
				(float) $config->get("cost", 0),
				(int) $config->get("icon-type", Kit::ICON_NONE),
				(string) $config->get("icon", ""),
				array_values((array) $config->get("items", [])),
				array_values((array) $config->get("armor", [])),
				array_values((array) $config->get("commands", []))
			);
		}
		ksort($this->kits);
		$this->registerKitPermissions();
	}

	private function registerKitPermissions() : void{
		$manager = PermissionManager::getInstance();
		$operatorRoot = $manager->getPermission(DefaultPermissions::ROOT_OPERATOR);
		if($operatorRoot === null){
			return;
		}
		foreach($this->kits as $kit){
			$node = "essentialsz.kits." . mb_strtolower($kit->name);
			if($manager->getPermission($node) === null){
				DefaultPermissions::registerPermission(new Permission($node, "Allows use of the " . $kit->name . " kit"), [$operatorRoot]);
			}
		}
	}

	public static function isValidName(string $name) : bool{
		return preg_match("/^[A-Za-z0-9 _-]{1,32}$/", $name) === 1;
	}

	private function getFile(string $name) : string{
		$safe = (string) preg_replace("/[^a-z0-9_-]/", "_", mb_strtolower(trim($name)));
		return $this->getFolder() . DIRECTORY_SEPARATOR . $safe . ".yml";
	}

	/**
	 * @return list<string>
	 */
	public function getList() : array{
		$names = [];
		foreach($this->kits as $kit){
			$names[] = $kit->name;
		}
		return $names;
	}

	/**
	 * @return list<Kit>
	 */
	public function getAll() : array{
		return array_values($this->kits);
	}

	public function getKit(string $name) : ?Kit{
		return $this->kits[mb_strtolower(trim($name))] ?? null;
	}

	public function setKit(Kit $kit) : void{
		$config = new Config($this->getFile($kit->name), Config::YAML);
		$config->setAll([
			"name" => $kit->name,
			"display-name" => $kit->displayName,
			"delay" => $kit->delay,
			"cost" => $kit->cost,
			"icon-type" => $kit->iconType,
			"icon" => $kit->icon,
			"items" => $kit->items,
			"armor" => $kit->armor,
			"commands" => $kit->commands
		]);
		$config->save();

		$this->kits[mb_strtolower($kit->name)] = $kit;
		ksort($this->kits);
		$this->registerKitPermissions();
	}

	public function removeKit(string $name) : bool{
		$kit = $this->getKit($name);
		if($kit === null){
			return false;
		}
		unset($this->kits[mb_strtolower($kit->name)]);
		$file = $this->getFile($kit->name);
		if(file_exists($file)){
			@unlink($file);
		}
		return true;
	}

	public function count() : int{
		return count($this->kits);
	}

	public static function encodeItem(Item $item) : array{
		return ["nbt_b64" => base64_encode((new LittleEndianNbtSerializer())->write(new TreeRoot($item->nbtSerialize())))];
	}

	/** @param array|string $entry an nbt_b64 map, an {id, count} map, or a "name [count]" string */
	public static function decodeItem(array|string $entry) : ?Item{
		if(is_string($entry)){
			$parts = preg_split("/\\s+/", trim($entry)) ?: [];
			if(!isset($parts[0]) || $parts[0] === ""){
				return null;
			}
			$item = StringToItemParser::getInstance()->parse(mb_strtolower($parts[0]));
			if($item === null){
				return null;
			}
			if(isset($parts[1]) && is_numeric($parts[1])){
				$item->setCount(max(1, (int) $parts[1]));
			}
			return $item;
		}
		if(isset($entry["nbt_b64"]) && is_string($entry["nbt_b64"])){
			$raw = base64_decode($entry["nbt_b64"], true);
			if($raw === false){
				return null;
			}
			try{
				return Item::nbtDeserialize((new LittleEndianNbtSerializer())->read($raw)->mustGetCompoundTag());
			}catch(\Throwable){
				return null;
			}
		}
		if(isset($entry["id"]) && is_string($entry["id"])){
			$item = StringToItemParser::getInstance()->parse(mb_strtolower($entry["id"]));
			if($item === null){
				return null;
			}
			$item->setCount(max(1, (int) ($entry["count"] ?? 1)));
			return $item;
		}
		return null;
	}

	public function giveKit(User $user, Kit $kit) : void{
		$player = $user->getBase();

		$items = [];
		$resolvedAny = false;
		$brokenAny = false;
		foreach($kit->items as $entry){
			$item = self::decodeItem($entry);
			if($item === null){
				$brokenAny = true;
				continue;
			}
			$resolvedAny = true;
			$items[] = $item;
		}
		$armorInventory = $player->getArmorInventory();
		foreach($kit->armor as $entry){
			$item = self::decodeItem($entry);
			if($item === null){
				$brokenAny = true;
				continue;
			}
			$resolvedAny = true;
			if($item instanceof Armor && $armorInventory->getItem($item->getArmorSlot())->isNull()){
				$armorInventory->setItem($item->getArmorSlot(), $item);
			}else{
				$items[] = $item;
			}
		}
		if($brokenAny && !$resolvedAny && $kit->commands === []){
			throw new TranslatableException("kitError2");
		}

		$leftovers = $player->getInventory()->addItem(...$items);
		if($leftovers !== []){
			$user->sendTl("kitInvFull");
			foreach($leftovers as $leftover){
				$player->getWorld()->dropItem($player->getPosition(), $leftover);
			}
		}

		if($kit->commands !== []){
			$server = $this->ess->getServer();
			$consoleSender = new ConsoleCommandSender($server, $server->getLanguage());
			foreach($kit->commands as $command){
				$server->dispatchCommand($consoleSender, str_replace(
					["{player}", "{display-name}"],
					[$user->getName(), $user->getDisplayName()],
					$command
				));
			}
		}
	}
}
