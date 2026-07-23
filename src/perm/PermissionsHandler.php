<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\perm;

use Doma\EssentialsZ\EssentialsZ;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;

/**
 * Registers every permission node at runtime against the PermissionManager -
 * nothing is declared in plugin.yml. All nodes default to operators; grant
 * them to players/groups with a permissions plugin of your choice.
 */
final class PermissionsHandler{

	/** @var array<string, string> node => description */
	private const PERMISSIONS = [
		"essentialsz.gamemode" => "Allows access to the /gamemode command",
		"essentialsz.gamemode.all" => "Allows changing to every game mode",
		"essentialsz.gamemode.survival" => "Allows changing to survival mode",
		"essentialsz.gamemode.creative" => "Allows changing to creative mode",
		"essentialsz.gamemode.adventure" => "Allows changing to adventure mode",
		"essentialsz.gamemode.spectator" => "Allows changing to spectator mode",
		"essentialsz.gamemode.others" => "Allows changing the game mode of other players",
		"essentialsz.essentials" => "Allows access to the /essentials command",
		"essentialsz.fly" => "Allows access to the /fly command",
		"essentialsz.fly.others" => "Allows toggling fly for other players",
		"essentialsz.god" => "Allows access to the /god command",
		"essentialsz.god.others" => "Allows toggling god mode for other players",
		"essentialsz.god.pvp" => "Allows attacking players while in god mode",
		"essentialsz.heal" => "Allows access to the /heal command",
		"essentialsz.heal.others" => "Allows healing other players",
		"essentialsz.feed" => "Allows access to the /feed command",
		"essentialsz.feed.others" => "Allows feeding other players",
		"essentialsz.speed" => "Allows access to the /speed command",
		"essentialsz.speed.others" => "Allows changing the speed of other players",
		"essentialsz.speed.fly" => "Allows changing the fly speed",
		"essentialsz.speed.walk" => "Allows changing the walk speed",
		"essentialsz.speed.bypass" => "Bypasses the configured speed limits",
		"essentialsz.vanish" => "Allows access to the /vanish command",
		"essentialsz.vanish.others" => "Allows toggling vanish for other players",
		"essentialsz.vanish.see" => "Allows seeing vanished players",
		"essentialsz.vanish.onjoin" => "Vanishes the player silently on join",
		"essentialsz.vanish.pvp" => "Allows attacking players while vanished",
		"essentialsz.afk" => "Allows access to the /afk command",
		"essentialsz.afk.others" => "Allows toggling AFK for other players",
		"essentialsz.afk.message" => "Allows setting an AFK message",
		"essentialsz.tpa" => "Allows access to the /tpa command",
		"essentialsz.tpaccept" => "Allows accepting teleport requests",
		"essentialsz.tpahere" => "Allows access to the /tpahere command",
		"essentialsz.tpdeny" => "Allows denying teleport requests",
		"essentialsz.back" => "Allows access to the /back command",
		"essentialsz.back.others" => "Allows returning other players to their death location",
		"essentialsz.spawn" => "Allows access to the /spawn command",
		"essentialsz.spawn.others" => "Allows sending other players to spawn",
		"essentialsz.setspawn" => "Allows setting the server spawn",
		"essentialsz.warp" => "Allows access to the /warp command",
		"essentialsz.warp.list" => "Allows listing warps",
		"essentialsz.warp.others" => "Allows warping other players",
		"essentialsz.warp.admin" => "Allows access to the warp admin UI",
		"essentialsz.home" => "Allows access to the /home command",
		"essentialsz.sethome" => "Allows setting homes",
		"essentialsz.sethome.multiple.unlimited" => "Bypasses the max-homes limit",
		"essentialsz.delhome" => "Allows deleting homes",
		"essentialsz.tpr" => "Allows access to the /tpr command",
		"essentialsz.tpr.others" => "Allows randomly teleporting other players",
		"essentialsz.tp" => "Allows access to the /tp command",
		"essentialsz.tp.others" => "Allows teleporting other players",
		"essentialsz.tp.position" => "Allows teleporting to coordinates with /tp",
		"essentialsz.tphere" => "Allows access to the /tphere command",
		"essentialsz.tppos" => "Allows access to the /tppos command",
		"essentialsz.tpo" => "Allows access to the /tpo command",
		"essentialsz.setwarp" => "Allows creating warps with /setwarp",
		"essentialsz.delwarp" => "Allows deleting warps with /delwarp",
		"essentialsz.warp.overwrite" => "Allows moving existing warps with /setwarp",
		"essentialsz.home.others" => "Allows teleporting to other players' homes",
		"essentialsz.sethome.others" => "Allows setting other players' homes",
		"essentialsz.delhome.others" => "Allows deleting other players' homes",
		"essentialsz.kit" => "Allows access to the /kit command",
		"essentialsz.kit.others" => "Allows giving kits to other players",
		"essentialsz.kit.admin" => "Allows access to the kit admin UI",
		"essentialsz.kit.exemptdelay" => "Bypasses kit cooldowns",
		"essentialsz.time" => "Allows access to the /time command",
		"essentialsz.time.set" => "Allows changing the world time",
		"essentialsz.time.world.all" => "Allows changing the time in every world",
		"essentialsz.category" => "Allows viewing every kit category"
	];

	/** @var array<string, string> node => description, registered only when the economy module is enabled */
	private const ECONOMY_PERMISSIONS = [
		"essentialsz.balance" => "Allows access to the /balance command",
		"essentialsz.balance.others" => "Allows viewing the balance of other players",
		"essentialsz.pay" => "Allows access to the /pay command",
		"essentialsz.balancetop" => "Allows access to the /balancetop command",
		"essentialsz.mystatus" => "Allows access to the /mystatus command",
		"essentialsz.eco" => "Allows access to the /eco admin command",
		"essentialsz.givemoney" => "Allows adding money to a player's balance",
		"essentialsz.takemoney" => "Allows taking money from a player's balance",
		"essentialsz.setmoney" => "Allows setting a player's balance"
	];

	public function __construct(private EssentialsZ $ess){}

	public function registerPermissions() : void{
		$this->registerAll(self::PERMISSIONS);
	}

	public function registerEconomyPermissions() : void{
		$this->registerAll(self::ECONOMY_PERMISSIONS);
	}

	/**
	 * @param array<string, string> $permissions node => description
	 */
	private function registerAll(array $permissions) : void{
		$manager = PermissionManager::getInstance();
		$operatorRoot = $manager->getPermission(DefaultPermissions::ROOT_OPERATOR);
		if($operatorRoot === null){
			throw new \RuntimeException("Operator root permission is not registered");
		}

		foreach($permissions as $node => $description){
			if($manager->getPermission($node) !== null){
				continue; // already registered (e.g. after /essentials reload)
			}
			DefaultPermissions::registerPermission(new Permission($node, $description), [$operatorRoot]);
		}
	}
}
