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

use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use function array_values;
use function basename;
use function count;
use function file_exists;
use function glob;
use function is_dir;
use function ksort;
use function mb_strtolower;
use function mkdir;
use function preg_match;
use function preg_replace;
use function substr;
use function trim;
use function unlink;
use Doma\EssentialsZ\IEssentials;

final class Categories{

	/** @var array<string, Category> */
	private array $categories = [];

	public function __construct(private IEssentials $ess){
		$this->reloadConfig();
	}

	private function getFolder() : string{
		$folder = $this->ess->getDataFolder() . "categories";
		if(!is_dir($folder)){
			@mkdir($folder, 0777, true);
		}
		return $folder;
	}

	public function reloadConfig() : void{
		$this->categories = [];
		foreach(glob($this->getFolder() . DIRECTORY_SEPARATOR . "*.yml") ?: [] as $file){
			$config = new \pocketmine\utils\Config($file, \pocketmine\utils\Config::YAML);
			$name = (string) $config->get("name", substr(basename($file), 0, -4));
			if($name === ""){
				continue;
			}
			$this->categories[mb_strtolower($name)] = new Category(
				$name,
				(string) $config->get("display-name", $name),
				(int) $config->get("icon-type", Category::ICON_NONE),
				(string) $config->get("icon", ""),
				(bool) $config->get("locked", true),
				array_values((array) $config->get("kits", []))
			);
		}
		ksort($this->categories);
		$this->registerPermissions();
	}

	private function registerPermissions() : void{
		$manager = PermissionManager::getInstance();
		$operatorRoot = $manager->getPermission(DefaultPermissions::ROOT_OPERATOR);
		if($operatorRoot === null){
			return;
		}
		foreach($this->categories as $category){
			$node = $category->permissionNode();
			if($manager->getPermission($node) === null){
				DefaultPermissions::registerPermission(new Permission($node, "Allows viewing the " . $category->name . " kit category"), [$operatorRoot]);
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
	 * @return list<Category>
	 */
	public function getAll() : array{
		return array_values($this->categories);
	}

	public function getCategory(string $name) : ?Category{
		return $this->categories[mb_strtolower(trim($name))] ?? null;
	}

	public function setCategory(Category $category) : void{
		$config = new \pocketmine\utils\Config($this->getFile($category->name), \pocketmine\utils\Config::YAML);
		$config->setAll([
			"name" => $category->name,
			"display-name" => $category->displayName,
			"icon-type" => $category->iconType,
			"icon" => $category->icon,
			"locked" => $category->locked,
			"kits" => $category->kits
		]);
		$config->save();

		$this->categories[mb_strtolower($category->name)] = $category;
		ksort($this->categories);
		$this->registerPermissions();
	}

	public function removeCategory(string $name) : bool{
		$category = $this->getCategory($name);
		if($category === null){
			return false;
		}
		unset($this->categories[mb_strtolower($category->name)]);
		$file = $this->getFile($category->name);
		if(file_exists($file)){
			@unlink($file);
		}
		return true;
	}

	public function count() : int{
		return count($this->categories);
	}
}
