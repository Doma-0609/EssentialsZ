<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ;

use Doma\EssentialsZ\commands\Commandafk;
use Doma\EssentialsZ\commands\Commandback;
use Doma\EssentialsZ\commands\Commandbalance;
use Doma\EssentialsZ\commands\Commandbaltop;
use Doma\EssentialsZ\commands\Commanddelhome;
use Doma\EssentialsZ\commands\Commanddelwarp;
use Doma\EssentialsZ\commands\Commandeco;
use Doma\EssentialsZ\commands\Commandessentials;
use Doma\EssentialsZ\commands\Commandfeed;
use Doma\EssentialsZ\commands\Commandfly;
use Doma\EssentialsZ\commands\Commandgamemode;
use Doma\EssentialsZ\commands\Commandgivemoney;
use Doma\EssentialsZ\commands\Commandgod;
use Doma\EssentialsZ\commands\Commandheal;
use Doma\EssentialsZ\commands\Commandhome;
use Doma\EssentialsZ\commands\Commandkit;
use Doma\EssentialsZ\commands\Commandmystatus;
use Doma\EssentialsZ\commands\Commandpay;
use Doma\EssentialsZ\commands\Commandsetmoney;
use Doma\EssentialsZ\commands\Commandtakemoney;
use Doma\EssentialsZ\commands\Commandsethome;
use Doma\EssentialsZ\commands\Commandsetspawn;
use Doma\EssentialsZ\commands\Commandsetwarp;
use Doma\EssentialsZ\commands\Commandspawn;
use Doma\EssentialsZ\commands\Commandspeed;
use Doma\EssentialsZ\commands\Commandtp;
use Doma\EssentialsZ\commands\Commandtpa;
use Doma\EssentialsZ\commands\Commandtpaccept;
use Doma\EssentialsZ\commands\Commandtpahere;
use Doma\EssentialsZ\commands\Commandtpdeny;
use Doma\EssentialsZ\commands\Commandtphere;
use Doma\EssentialsZ\commands\Commandtpo;
use Doma\EssentialsZ\commands\Commandtppos;
use Doma\EssentialsZ\commands\Commandtpr;
use Doma\EssentialsZ\commands\Commandvanish;
use Doma\EssentialsZ\commands\Commandwarp;
use Doma\EssentialsZ\commands\CommandDisabler;
use Doma\EssentialsZ\commands\EssentialsPluginCommand;
use Doma\EssentialsZ\commands\IEssentialsCommand;
use Doma\EssentialsZ\config\EssentialsConfiguration;
use Doma\EssentialsZ\economy\EconomyListener;
use Doma\EssentialsZ\economy\EconomySettings;
use Doma\EssentialsZ\economy\EssentialsEconomy;
use Doma\EssentialsZ\perm\PermissionsHandler;
use Doma\EssentialsZ\storage\DataProvider;
use Doma\EssentialsZ\storage\JsonDataProvider;
use Doma\EssentialsZ\storage\StorageSettings;
use Doma\EssentialsZ\session\ModernUserMap;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use Doma\EssentialsZ\kit\Categories;
use Doma\EssentialsZ\kit\Kits;
use Doma\EssentialsZ\listener\EssentialsEntityListener;
use Doma\EssentialsZ\listener\EssentialsPlayerListener;
use Doma\EssentialsZ\rtl\RtlListener;
use Doma\EssentialsZ\rtl\RtlProcessor;
use Doma\EssentialsZ\rtl\RtlSettings;
use Doma\EssentialsZ\session\User;
use Doma\EssentialsZ\teleport\RandomTeleport;
use Doma\EssentialsZ\teleport\Spawn;
use Doma\EssentialsZ\warp\Warps;

class EssentialsZ extends PluginBase implements IEssentials{

	private I18n $i18n;
	private Settings $settings;
	private DataProvider $dataProvider;
	private ModernUserMap $userMap;
	private PermissionsHandler $permissionsHandler;
	private Warps $warps;
	private Kits $kits;
	private Categories $categories;
	private Spawn $spawn;
	private RandomTeleport $randomTeleport;
	private ?EssentialsEconomy $economy = null;
	private ?RtlProcessor $rtl = null;

	/** @var array<string, IEssentialsCommand> */
	private array $commands = [];

	protected function onEnable() : void{
		// Boot order: resources -> I18n -> Settings -> user map ->
		// permissions -> commands -> listeners.
		$this->saveDefaultConfig();
		$this->saveResource("messages/messages_en.properties");
		$this->saveResource("messages/messages_fa.properties");

		$this->i18n = new I18n($this);
		$this->i18n->onEnable();

		$this->settings = new Settings($this);
		$this->i18n->updateLocale($this->settings->getLocale());

		$config = new EssentialsConfiguration($this->getDataFolder() . "config.yml");
		$config->load();
		$this->dataProvider = $this->openDataProvider(StorageSettings::fromConfig($config));

		$this->userMap = new ModernUserMap($this);
		$this->warps = new Warps($this);
		$this->kits = new Kits($this);
		$this->categories = new Categories($this);
		$this->spawn = new Spawn($this);
		$this->randomTeleport = new RandomTeleport($this);

		if($config->getBoolean("economy.enabled", true)){
			$this->economy = new EssentialsEconomy($this, EconomySettings::fromConfig($config));
		}
		if($config->getBoolean("rtl.enabled", false)){
			$this->rtl = new RtlProcessor(RtlSettings::fromConfig($config));
		}

		$this->permissionsHandler = new PermissionsHandler($this);
		$this->permissionsHandler->registerPermissions();
		if($this->economy !== null){
			$this->permissionsHandler->registerEconomyPermissions();
		}

		$this->registerCommands();

		$pluginManager = $this->getServer()->getPluginManager();
		$pluginManager->registerEvents(new EssentialsPlayerListener($this), $this);
		$pluginManager->registerEvents(new EssentialsEntityListener($this), $this);
		if($this->economy !== null){
			$pluginManager->registerEvents(new EconomyListener($this->economy), $this);
		}
		if($this->rtl !== null){
			$pluginManager->registerEvents(new RtlListener($this->rtl), $this);
		}

		// Last, so listed commands can be removed no matter who registered them.
		$missing = CommandDisabler::disable($this->getServer()->getCommandMap(), $config->getList("disabled-commands"));
		foreach($missing as $label){
			$this->getLogger()->warning(I18n::tlLiteral("disabledCommandNotFound", $label));
		}
	}

	protected function onDisable() : void{
		if(isset($this->userMap)){
			$this->userMap->shutdown();
		}
		if(isset($this->dataProvider)){
			$this->dataProvider->close();
		}
		if(isset($this->i18n)){
			$this->i18n->onDisable();
		}
	}

	/**
	 * Opens the configured storage backend, falling back to JSON when it
	 * cannot be reached so player data still works.
	 */
	private function openDataProvider(StorageSettings $settings) : DataProvider{
		$provider = $settings->createProvider($this->getDataFolder());
		try{
			$provider->init();
			return $provider;
		}catch(\RuntimeException $e){
			$this->getLogger()->error(I18n::tlLiteral("storageFallback", $provider->getName(), $e->getMessage()));
			$fallback = new JsonDataProvider($this->getDataFolder() . "players");
			$fallback->init();
			return $fallback;
		}
	}

	/**
	 * Commands are registered ONLY through the server CommandMap - nothing
	 * is declared in plugin.yml. Short-form aliases (/gmc, /gmt, ...)
	 * resolve their behaviour from the command label they were invoked with.
	 */
	private function registerCommands() : void{
		$commandMap = $this->getServer()->getCommandMap();
		$version = $this->getDescription()->getVersion();

		$defs = [
			[
				new Commandgamemode(),
				[
					"adventure", "eadventure", "adventuremode", "eadventuremode",
					"creative", "ecreative", "eecreative", "creativemode", "ecreativemode",
					"egamemode", "gm", "egm",
					"gma", "egma", "gmc", "egmc", "gms", "egms", "gmt", "egmt",
					"survival", "esurvival", "survivalmode", "esurvivalmode",
					"gmsp", "egmsp", "sp", "spec", "spectator"
				]
			],
			[
				new Commandessentials($version),
				["eessentials", "ess", "eess", "essversion", "essentialsz", "essz"]
			],
			[new Commandfly(), ["efly"]],
			[new Commandgod(), ["egod", "godmode", "egodmode", "tgm", "etgm"]],
			[new Commandheal(), ["eheal"]],
			[new Commandfeed(), ["eat", "eeat", "efeed"]],
			[
				new Commandspeed(),
				["flyspeed", "eflyspeed", "fspeed", "efspeed", "espeed", "walkspeed", "ewalkspeed", "wspeed", "ewspeed"]
			],
			[new Commandvanish(), ["v", "ev", "evanish"]],
			[new Commandafk(), ["eafk", "away", "eaway"]],
			[new Commandtpa(), ["call", "ecall", "etpa", "tpask", "etpask"]],
			[new Commandtpaccept(), ["etpaccept", "tpyes", "etpyes"]],
			[new Commandtpahere(), ["etpahere"]],
			[new Commandtpdeny(), ["etpdeny", "tpno", "etpno"]],
			[new Commandback(), ["eback", "return", "ereturn"]],
			[new Commandspawn(), ["espawn"]],
			[new Commandsetspawn(), ["esetspawn"]],
			[new Commandwarp(), ["ewarp", "warps", "ewarps"]],
			[new Commandhome(), ["ehome", "homes", "ehomes"]],
			[new Commandsethome(), ["esethome", "createhome", "ecreatehome"]],
			[new Commanddelhome(), ["edelhome", "remhome", "eremhome", "rmhome", "ermhome"]],
			[new Commandtpr(), ["rtp", "ertp", "etpr", "tprandom", "etprandom"]],
			[new Commandtp(), ["tele", "etele", "teleport", "eteleport", "etp", "tp2p", "etp2p"]],
			[new Commandtphere(), ["s", "etphere"]],
			[new Commandtppos(), ["etppos"]],
			[new Commandtpo(), ["etpo"]],
			[new Commandsetwarp(), ["createwarp", "ecreatewarp", "esetwarp"]],
			[new Commanddelwarp(), ["edelwarp", "remwarp", "eremwarp", "rmwarp", "ermwarp"]],
			[new Commandkit(), ["kits", "ekit", "ekits"]]
		];

		if($this->economy !== null){
			$defs[] = [new Commandbalance(), ["bal", "ebal", "ebalance", "money", "emoney", "seemoney", "eseemoney"]];
			$defs[] = [new Commandpay(), ["epay"]];
			$defs[] = [new Commandbaltop(), ["baltop", "ebaltop", "ebalancetop", "rich", "erich", "topmoney", "etopmoney"]];
			$defs[] = [new Commandeco(), ["economy", "eeco", "eeconomy"]];
			$defs[] = [new Commandgivemoney(), ["egivemoney", "addmoney", "eaddmoney", "addbalance", "eaddbalance"]];
			$defs[] = [new Commandtakemoney(), ["etakemoney", "removemoney", "eremovemoney", "removebalance", "eremovebalance"]];
			$defs[] = [new Commandsetmoney(), ["esetmoney", "setbalance", "esetbalance"]];
			$defs[] = [new Commandmystatus(), ["emystatus", "status", "estatus"]];
		}

		foreach($defs as [$command, $aliases]){
			/** @var IEssentialsCommand $command */
			$command->setEssentials($this);
			$this->commands[$command->getName()] = $command;

			$name = $command->getName();
			$description = I18n::tlLiteral($name . "CommandDescription");
			$usage = I18n::tlLiteral($name . "CommandUsage");

			// Take over the primary label and every alias from whatever holds
			// them (the vanilla /gamemode, or another plugin) so our command
			// answers to the unprefixed name instead of essentialsz:<name>.
			foreach([$name, ...$aliases] as $label){
				$existing = $commandMap->getCommand($label);
				if($existing !== null && !($existing instanceof EssentialsPluginCommand)){
					$commandMap->unregister($existing);
				}
			}

			$commandMap->register("essentialsz", new EssentialsPluginCommand($this, $command, $description, $usage, $aliases));
		}
	}

	public function getSettings() : ISettings{
		return $this->settings;
	}

	public function getI18n() : I18n{
		return $this->i18n;
	}

	public function getDataProvider() : DataProvider{
		return $this->dataProvider;
	}

	public function getUsers() : ModernUserMap{
		return $this->userMap;
	}

	public function getWarps() : Warps{
		return $this->warps;
	}

	public function getKits() : Kits{
		return $this->kits;
	}

	public function getCategories() : Categories{
		return $this->categories;
	}

	/**
	 * The economy API, or null when the economy module is disabled.
	 */
	public function getEconomy() : ?EssentialsEconomy{
		return $this->economy;
	}

	/**
	 * The right-to-left text API, or null when the RTL module is disabled.
	 */
	public function getRtl() : ?RtlProcessor{
		return $this->rtl;
	}

	public function getSpawn() : Spawn{
		return $this->spawn;
	}

	public function getRandomTeleport() : RandomTeleport{
		return $this->randomTeleport;
	}

	public function getUser(Player $base) : User{
		return $this->userMap->getUser($base);
	}

	/**
	 * @return list<User>
	 */
	public function getOnlineUsers() : array{
		return $this->userMap->getOnlineUsers();
	}

	/**
	 * Reloads the config and message bundles.
	 */
	public function reload() : void{
		$this->reloadConfig();
		$this->settings->reloadConfig();
		$this->i18n->updateLocale($this->settings->getLocale());
		$this->warps->reloadConfig();
		$this->kits->reloadConfig();
		$this->categories->reloadConfig();
		$this->spawn->reloadConfig();
	}

	/**
	 * @return array<string, IEssentialsCommand>
	 */
	public function getEssentialsCommands() : array{
		return $this->commands;
	}
}
