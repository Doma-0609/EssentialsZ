<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\commands;

use Doma\EssentialsZ\session\CommandSource;
use Doma\EssentialsZ\warp\WarpForms;
use Doma\EssentialsZ\session\User;
use pocketmine\Server;
use function ceil;
use function count;
use function implode;
use function max;
use function min;
use function preg_match;
use function strtolower;

class Commandwarp extends EssentialsCommand{

	private const WARPS_PER_PAGE = 20;

	public function __construct(){
		parent::__construct("warp");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) > 0 && strtolower($args[0]) === "admin"){
			if(!$user->isAuthorized("essentialsz.warp.admin")){
				throw new TranslatableException("noAccessCommand");
			}
			(new WarpForms($this->ess))->openAdminMenu($user);
			return;
		}

		if(count($args) === 0 || preg_match("/^[0-9]+$/", $args[0]) === 1){
			if(!$user->isAuthorized("essentialsz.warp.list")){
				throw new TranslatableException("warpListPermission");
			}
			if(count($args) === 0){
				(new WarpForms($this->ess))->openWarpList($user);
				return;
			}
			$this->warpList($user->getSource(), $args);
			return;
		}

		if(count($args) === 2 && $user->isAuthorized("essentialsz.warp.others")){
			$otherUser = $this->getPlayer($server, $user->getSource(), $args[1]);
			self::warpUser($this->ess, $otherUser, $args[0]);
			return;
		}
		self::warpUser($this->ess, $user, $args[0]);
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) < 2 || preg_match("/^[0-9]+$/", $args[0]) === 1){
			$this->warpList($sender, $args);
			return;
		}
		$otherUser = $this->getPlayer($server, $sender, $args[1]);
		self::warpUser($this->ess, $otherUser, $args[0]);
	}

	private function warpList(CommandSource $sender, array $args) : void{
		$warps = $this->ess->getWarps();
		$names = $warps->getList();
		if($names === []){
			throw new TranslatableException("noWarpsDefined");
		}

		$page = count($args) > 0 && preg_match("/^[0-9]+$/", $args[0]) === 1 ? max(1, (int) $args[0]) : 1;
		$maxPages = (int) ceil(count($names) / self::WARPS_PER_PAGE);
		$page = min($page, $maxPages);
		$slice = \array_slice($names, ($page - 1) * self::WARPS_PER_PAGE, self::WARPS_PER_PAGE);

		if(count($names) > self::WARPS_PER_PAGE){
			$sender->sendTl("warpsCount", count($names), $page, $maxPages);
			$sender->sendTl("warpList", implode(", ", $slice));
		}else{
			$sender->sendTl("warps", implode(", ", $slice));
		}
	}

	public static function warpUser(\Doma\EssentialsZ\IEssentials $ess, User $user, string $name) : void{
		$warp = $ess->getWarps()->getWarp($name);
		if($warp === null){
			throw new TranslatableException("warpNotExist");
		}
		$location = $warp->getLocation($user->getBase()->getServer());
		if($location === null){
			throw new TranslatableException("invalidWorld");
		}
		$user->sendTl("warpingTo", $warp->displayName);
		$user->getBase()->teleport($location);
	}
}
