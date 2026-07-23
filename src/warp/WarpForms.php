<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\warp;

use Doma\EssentialsZ\commands\Commandwarp;
use Doma\EssentialsZ\commands\TranslatableException;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\session\User;
use Doma\EssentialsZ\warp\Warp;
use Doma\EssentialsZ\warp\Warps;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;
use function trim;

final class WarpForms{

	public function __construct(private IEssentials $ess){}

	private function tl(User $user, string $key, string|int|float ...$args) : string{
		return $user->playerTl($key, ...$args);
	}

	public function openWarpList(User $user) : void{
		$warps = $this->ess->getWarps()->getAll();
		if($warps === []){
			throw new TranslatableException("noWarpsDefined");
		}

		$ess = $this->ess;
		$names = [];
		foreach($warps as $warp){
			$names[] = $warp->name;
		}
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess, $names) : void{
			if($data === null || !isset($names[$data])){
				return;
			}
			$user = $ess->getUser($player);
			try{
				Commandwarp::warpUser($ess, $user, $names[$data]);
			}catch(TranslatableException $e){
				$user->sendMessage($e->getMessage());
			}
		});
		$form->setTitle($this->tl($user, "warpUiTitle"));
		foreach($warps as $warp){
			if($warp->iconType === Warp::ICON_NONE || $warp->icon === ""){
				$form->addButton($warp->displayName);
			}else{
				$form->addButton($warp->displayName, $warp->iconType, $warp->icon);
			}
		}
		$user->getBase()->sendForm($form);
	}

	public function openAdminMenu(User $user) : void{
		$forms = $this;
		$ess = $this->ess;
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($forms, $ess) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);
			try{
				match($data){
					0 => $forms->openAddForm($user),
					1 => $forms->openRemoveForm($user),
					2 => $forms->sendAdminList($user),
					default => null
				};
			}catch(TranslatableException $e){
				$user->sendMessage($e->getMessage());
			}
		});
		$form->setTitle($this->tl($user, "warpUiAdminTitle"));
		$form->addButton($this->tl($user, "warpUiAdd"));
		$form->addButton($this->tl($user, "warpUiRemove"));
		$form->addButton($this->tl($user, "warpUiList"));
		$user->getBase()->sendForm($form);
	}

	public function openAddForm(User $user) : void{
		$ess = $this->ess;
		$form = new CustomForm(static function(Player $player, ?array $data) use ($ess) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);

			$name = trim((string) ($data[0] ?? ""));
			$displayName = trim((string) ($data[1] ?? ""));
			$iconType = [Warp::ICON_NONE, Warp::ICON_PATH, Warp::ICON_URL][(int) ($data[2] ?? 0)] ?? Warp::ICON_NONE;
			$icon = trim((string) ($data[3] ?? ""));

			if($name === "" || !Warps::isValidName($name)){
				$user->sendTl("invalidWarpName");
				return;
			}
			if($displayName === ""){
				$displayName = $name;
			}
			if($icon === ""){
				$iconType = Warp::ICON_NONE;
			}

			$ess->getWarps()->setWarp($name, $displayName, $iconType, $icon, $player->getLocation());
			$user->sendTl("warpSet", $name);
		});
		$form->setTitle($this->tl($user, "warpUiAddTitle"));
		$form->addInput($this->tl($user, "warpUiName"), "spawn");
		$form->addInput($this->tl($user, "warpUiDisplayName"), "§bSpawn");
		$form->addDropdown($this->tl($user, "warpUiIconType"), [
			$this->tl($user, "warpUiIconNone"),
			$this->tl($user, "warpUiIconPath"),
			$this->tl($user, "warpUiIconUrl")
		]);
		$form->addInput($this->tl($user, "warpUiIcon"), "textures/blocks/grass_side");
		$user->getBase()->sendForm($form);
	}

	public function openRemoveForm(User $user) : void{
		$warps = $this->ess->getWarps()->getAll();
		if($warps === []){
			throw new TranslatableException("noWarpsDefined");
		}

		$ess = $this->ess;
		$names = [];
		foreach($warps as $warp){
			$names[] = $warp->name;
		}
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess, $names) : void{
			if($data === null || !isset($names[$data])){
				return;
			}
			$user = $ess->getUser($player);
			if($ess->getWarps()->removeWarp($names[$data])){
				$user->sendTl("deleteWarp", $names[$data]);
			}else{
				$user->sendTl("warpNotExist");
			}
		});
		$form->setTitle($this->tl($user, "warpUiRemoveTitle"));
		foreach($warps as $warp){
			if($warp->iconType === Warp::ICON_NONE || $warp->icon === ""){
				$form->addButton($warp->displayName);
			}else{
				$form->addButton($warp->displayName, $warp->iconType, $warp->icon);
			}
		}
		$user->getBase()->sendForm($form);
	}

	public function sendAdminList(User $user) : void{
		$warps = $this->ess->getWarps()->getAll();
		if($warps === []){
			throw new TranslatableException("noWarpsDefined");
		}
		foreach($warps as $warp){
			$type = match($warp->iconType){
				Warp::ICON_PATH => "path: " . $warp->icon,
				Warp::ICON_URL => "url: " . $warp->icon,
				default => "no icon"
			};
			$user->sendTl("warpAdminEntry", $warp->name, $warp->displayName, $type);
		}
	}
}
