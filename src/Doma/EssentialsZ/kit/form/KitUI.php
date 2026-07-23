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

use Doma\EssentialsZ\kit\Category;
use Doma\EssentialsZ\kit\form\AdminMenuForm;
use Doma\EssentialsZ\kit\form\CategoryMenuForm;
use Doma\EssentialsZ\kit\form\KitListForm;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\kit\Kit;
use Doma\EssentialsZ\session\User;

/**
 * Entry point for the kit interface. When categories exist the player picks
 * one first, otherwise every kit they may claim is shown at once.
 */
final class KitUI{

	public static function openMenu(IEssentials $ess, User $user) : void{
		if($ess->getCategories()->count() > 0){
			CategoryMenuForm::open($ess, $user);
		}else{
			KitListForm::open($ess, $user, null);
		}
	}

	public static function openAdmin(IEssentials $ess, User $user) : void{
		AdminMenuForm::open($ess, $user);
	}

	public static function categoryVisibleTo(User $user, Category $category) : bool{
		return !$category->locked
			|| $user->isAuthorized("essentialsz.category")
			|| $user->isAuthorized($category->permissionNode());
	}

	public static function canClaim(User $user, Kit $kit) : bool{
		return $user->isAuthorized("essentialsz.kits." . \mb_strtolower($kit->name));
	}
}
