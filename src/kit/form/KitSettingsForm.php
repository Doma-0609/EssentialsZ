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
use function trim;
use jojoe77777\FormAPI\CustomForm;

/**
 * Edits a kit's settings. The name is fixed; toggle "recapture" to replace
 * its items with the editor's current inventory. Claim commands are edited
 * separately in KitCommandsForm.
 */
final class KitSettingsForm{

	public static function open(IEssentials $ess, User $user, string $kitName) : void{
		$kit = $ess->getKits()->getKit($kitName);
		if($kit === null){
			$user->sendTl("kitNotFound");
			return;
		}
		$form = new CustomForm(static function(Player $player, ?array $data) use ($ess, $kitName) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);
			$kit = $ess->getKits()->getKit($kitName);
			if($kit === null){
				$user->sendTl("kitNotFound");
				return;
			}

			$displayName = trim((string) ($data[0] ?? ""));
			$delayRaw = trim((string) ($data[1] ?? "0"));
			$costRaw = trim((string) ($data[2] ?? "0"));
			$iconType = [Kit::ICON_NONE, Kit::ICON_PATH, Kit::ICON_URL][(int) ($data[3] ?? 0)] ?? Kit::ICON_NONE;
			$icon = trim((string) ($data[4] ?? ""));
			$recapture = (bool) ($data[5] ?? false);

			if(!KitFormFields::isNumericOrEmpty($delayRaw) || !KitFormFields::isNumericOrEmpty($costRaw)){
				$user->sendTl("kitUiInvalidNumber");
				return;
			}
			if($displayName === ""){
				$displayName = $kit->name;
			}
			if($icon === ""){
				$iconType = Kit::ICON_NONE;
			}

			$items = $recapture ? KitFormFields::snapshotInventory($player) : $kit->items;
			$armor = $recapture ? KitFormFields::snapshotArmor($player) : $kit->armor;

			$ess->getKits()->setKit(new Kit(
				$kit->name,
				$displayName,
				$delayRaw === "" ? 0.0 : (float) $delayRaw,
				$costRaw === "" ? 0.0 : (float) $costRaw,
				$iconType,
				$icon,
				$items,
				$armor,
				$kit->commands
			));
			$user->sendTl("kitUiUpdated", $kit->name);
			KitEditMenuForm::open($ess, $user, $kit->name);
		});
		$form->setTitle($user->playerTl("kitUiEditKitTitle", $kit->name));
		$form->addInput($user->playerTl("kitUiDisplayName"), $kit->name, $kit->displayName);
		$form->addInput($user->playerTl("kitUiDelay"), "0", (string) $kit->delay);
		$form->addInput($user->playerTl("kitUiCost"), "0", (string) $kit->cost);
		$form->addDropdown($user->playerTl("kitUiIconType"), [
			$user->playerTl("kitUiIconNone"),
			$user->playerTl("kitUiIconPath"),
			$user->playerTl("kitUiIconUrl")
		], self::iconIndex($kit->iconType));
		$form->addInput($user->playerTl("kitUiIcon"), "textures/items/diamond_sword", $kit->icon);
		$form->addToggle($user->playerTl("kitUiRecapture"), false);
		$user->getBase()->sendForm($form);
	}

	private static function iconIndex(int $iconType) : int{
		return match($iconType){
			Kit::ICON_PATH => 1,
			Kit::ICON_URL => 2,
			default => 0
		};
	}
}
