<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\kit\form;

use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\session\User;
use pocketmine\player\Player;
use jojoe77777\FormAPI\SimpleForm;

final class KitPickForm{

	public const MODE_EDIT = "edit";
	public const MODE_DELETE = "delete";

	public static function open(IEssentials $ess, User $user, string $mode) : void{
		$kits = $ess->getKits()->getAll();
		if($kits === []){
			$user->sendTl("noKits");
			return;
		}

		$names = [];
		foreach($kits as $kit){
			$names[] = $kit->name;
		}
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess, $names, $mode) : void{
			if($data === null || !isset($names[$data])){
				return;
			}
			$user = $ess->getUser($player);
			$kit = $ess->getKits()->getKit($names[$data]);
			if($kit === null){
				$user->sendTl("kitNotFound");
				return;
			}
			if($mode === self::MODE_DELETE){
				$ess->getKits()->removeKit($kit->name);
				$user->sendTl("kitUiRemoved", $kit->name);
			}else{
				KitEditMenuForm::open($ess, $user, $kit->name);
			}
		});
		$form->setTitle($user->playerTl($mode === self::MODE_DELETE ? "kitUiRemoveTitle" : "kitUiEditTitle"));
		foreach($kits as $kit){
			$form->addButton($user->playerTl("kitUiEntryButton", $kit->displayName));
		}
		$user->getBase()->sendForm($form);
	}
}
