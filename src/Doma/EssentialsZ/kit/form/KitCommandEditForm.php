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
use Doma\EssentialsZ\kit\Kit;
use Doma\EssentialsZ\session\User;
use pocketmine\player\Player;
use function array_values;
use jojoe77777\FormAPI\CustomForm;

/**
 * Adds a command ($index null) or edits/removes an existing one. On submit
 * the kit is saved and the command list reopens.
 */
final class KitCommandEditForm{

	public static function open(IEssentials $ess, User $user, string $kitName, ?int $index) : void{
		$kit = $ess->getKits()->getKit($kitName);
		if($kit === null){
			$user->sendTl("kitNotFound");
			return;
		}
		$editing = $index !== null && isset($kit->commands[$index]);
		$current = $editing ? $kit->commands[$index] : "";

		$form = new CustomForm(static function(Player $player, ?array $data) use ($ess, $kitName, $index) : void{
			if($data === null){
				$user = $ess->getUser($player);
				KitCommandsForm::open($ess, $user, $kitName);
				return;
			}
			$user = $ess->getUser($player);
			$kit = $ess->getKits()->getKit($kitName);
			if($kit === null){
				$user->sendTl("kitNotFound");
				return;
			}

			$commands = $kit->commands;
			$editing = $index !== null && isset($commands[$index]);
			$command = KitFormFields::normalizeCommand((string) ($data[0] ?? ""));
			$remove = $editing && (bool) ($data[1] ?? false);

			if($remove || ($editing && $command === "")){
				unset($commands[$index]);
				$user->sendTl("kitUiCommandRemoved");
			}elseif($command === ""){
				$user->sendTl("kitUiCommandEmpty");
				KitCommandsForm::open($ess, $user, $kitName);
				return;
			}elseif($editing){
				$commands[$index] = $command;
				$user->sendTl("kitUiCommandUpdated");
			}else{
				$commands[] = $command;
				$user->sendTl("kitUiCommandAdded");
			}

			$ess->getKits()->setKit(new Kit(
				$kit->name,
				$kit->displayName,
				$kit->delay,
				$kit->cost,
				$kit->iconType,
				$kit->icon,
				$kit->items,
				$kit->armor,
				array_values($commands)
			));
			KitCommandsForm::open($ess, $user, $kitName);
		});
		$form->setTitle($user->playerTl($editing ? "kitUiCommandEditTitle" : "kitUiCommandAddTitle"));
		$form->addInput($user->playerTl("kitUiCommandInput"), $user->playerTl("kitUiCommandHint"), $current);
		if($editing){
			$form->addToggle($user->playerTl("kitUiCommandRemoveToggle"), false);
		}
		$user->getBase()->sendForm($form);
	}
}
