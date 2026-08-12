# EssentialsZ

**The essential plugin suite for PocketMine-MP 5.**

Gamemodes, player toggles, teleportation, homes, warps, kits, an economy and
right-to-left text support — in one plugin, with a clean developer API and no dependencies.

---

## Highlights

- **36 commands and over 150 aliases**, all registered at runtime — nothing is declared in
  `plugin.yml`, so labels never collide silently.
- **Takes over vanilla commands.** `/gamemode` and `/tp` replace the built-in ones and
  still honour the built-in permissions, so existing rank setups keep working.
- **One record per player.** Homes, balances, kit cooldowns and timestamps live in a
  single record backed by **JSON, SQLite or MySQL**.
- **Fully optional modules.** The economy and RTL modules register *nothing* when
  disabled — no commands, no permissions, no listeners, no database.
- **Form UI everywhere it helps** — warps, kits, kit categories and payments — with an
  automatic text fallback when no form library is installed.
- **413 translatable messages** in English and Persian, with optional per-player locale.
- **Documented developer API** for the economy, storage and RTL layers.

---

## Features

### Gamemode

`/gamemode` replaces the vanilla command and accepts every shorthand players expect:
mode names, numbers `0-3`, letters `s/c/a/sp`, and label shortcuts such as `/gmc`,
`/gms`, `/gma`, `/gmsp`, `/creative`, `/survival`. `/gmt` cycles
`survival → creative → adventure`. Console can target `*` for everyone online.

Switching **into** a mode is gated per mode (`essentialsz.gamemode.<mode>` or `.all`), and
the built-in `pocketmine.command.gamemode.self` / `.other` permissions keep working.

### Player toggles

| Command | What it does |
|---|---|
| `/fly` | Toggles flight; disabling also stops active flight |
| `/god` | Cancels damage and hunger drain, heals and feeds on enable |
| `/heal` | Restores health, food and air, extinguishes fire |
| `/feed` | Restores food and saturation, resets exhaustion |
| `/speed` | Fly/walk speed on a friendly `0-10` scale, capped by config |
| `/afk` | AFK broadcast, cleared automatically on movement or chat |

**`/vanish` is complete, not cosmetic:** the player is hidden from everyone without
`essentialsz.vanish.see`, removed from the in-game player list, and unreachable through
command targeting. A leave message is broadcast on vanish and a join message on unvanish —
both formats configurable. Players who join later still cannot see or list them, and a real
disconnect while vanished stays silent.

### Teleportation

Request-based teleports (`/tpa`, `/tpahere`, `/tpaccept`, `/tpdeny`) expire after a
configurable timeout and support `*` to answer every pending request at once.

Direct teleports cover `/tp` (player, player-to-player and coordinate forms with `~`
relative values), `/tphere`, `/tppos` (with optional yaw, pitch and world) and `/tpo`,
which reaches vanished players. `/back` returns to your last death location within a
configurable time limit, `/spawn` and `/setspawn` manage the server spawn, and `/tpr`
teleports to a random safe location within a configurable range.

### Homes and warps

Named homes are capped by `max-homes`, with an unlimited bypass permission. The
`player:name` form (`/home someone:base`) lets staff reach another **online** player's
homes, and `/delhome player:*` clears them all.

Warps are stored one file per warp with a separate identifier and coloured display name,
so `/warp spawn` jumps directly while lists show the pretty label. `/warp` with no
arguments opens a clickable list; `/warp admin` opens an admin menu to add, remove and
inspect warps, including a texture picker for button icons.

### Kits

Kits are defined one file per kit and support:

- **Full item fidelity** — enchantments, custom names and lore survive, because items are
  captured from your inventory as binary NBT. Hand-written kit files may also use simple
  `"apple 16"` strings.
- **Cooldowns** per player, with a negative delay meaning *one claim ever*, and a bypass
  permission.
- **Costs**, charged through the economy when it is enabled and ignored when it is not.
- **Claim commands** run from console on claim, with `{player}` and `{display-name}`
  placeholders.
- **Categories**, each with its own permission, so kits can be grouped into menus.
- **Per-kit permissions** (`essentialsz.kits.<name>`) registered automatically.

The `/kit admin` UI creates a kit from your current inventory, edits its settings, manages
categories, and has a dedicated command editor where commands are added one at a time.

### Economy

A self-contained economy with a documented API, switched off in one line if you already
run another economy plugin.

**Players:** `/balance`, `/pay`, `/balancetop`, `/mystatus` (wealth rank and share of all
money).
**Admins:** `/eco give|take|set|reset`, plus the standalone `/givemoney`, `/takemoney`
and `/setmoney`.

`/pay` refuses self-payment, honours a configurable minimum, and can pay **offline**
accounts. Balances are rounded to the configured decimals, clamped between zero and
`max-money`, and every write fires a cancellable event.

### Right-to-left text

Bedrock renders right-to-left runs backwards. With `rtl.enabled` on, every outgoing text
packet is corrected: each run of RTL characters is reshaped into joined letter forms,
reversed, and the runs are swapped end for end, while Latin text, numbers, punctuation and
colour codes keep their position. A single packet hook covers chat and system messages
alike. Letter reshaping can be turned off for order-only correction.

### Land claims

An optional land module lets players buy and protect a rectangular area. Turn it on with
`land.enabled` (buying charges the economy, so keep the economy module on for paid claims).

- **Buy** by marking two corners (`/land pos1`, `/land pos2`) and running `/land buy`. The
  price is `width x length x price-per-block`, and `min-size` / `max-size` bound each side.
- **Protection:** inside a claim, only the owner, invited players and holders of
  `essentialsz.land.bypass` may break, place, interact or trample farmland. Containers are
  protected by **their own position**, so a chest on a claim's edge can never be opened from
  an adjacent unclaimed block.
- **Manage** with `/land here|list|sell|invite <player>|kick <player>|invitee`, or open the
  form UI with `/land`.
- **Optimized:** claims are indexed by the world chunks they cover, so a per-block
  protection check only scans the handful of claims in that chunk, not every claim.
- `protected-worlds` also guards unclaimed land, `non-check-worlds` skips protection for
  speed, and the API is reachable at `EssentialsZ::getLand()` (`land\LandManager`).

### Disabling commands

Any command on the server can be removed by listing it under `disabled-commands`. This
runs **last** during startup, after EssentialsZ registers its own commands, so it reaches
vanilla commands, other plugins' commands and EssentialsZ's own alike:

```yml
disabled-commands:
  - me
  - about
  - ver
  - version
  - etc
```

A leading slash is optional, and the label may be a main name or an alias — either way the
whole command goes, aliases included. Labels that match nothing are reported in the log
instead of failing the boot.

### Storage

Every piece of persistent player data lives in **one record per player**, keyed by name,
UUID or XUID (your choice). Three backends ship with the plugin:

| Provider | Where it stores |
|---|---|
| `json` | `plugin_data/EssentialsZ/players/<key>.json` |
| `sqlite` | `plugin_data/EssentialsZ/storage/players.sqlite3` |
| `mysql` | Any MySQL server you point it at |

If the configured backend cannot be reached, the plugin logs the failure and falls back to
JSON so player data still works.

### Localization

Messages live in `.properties` bundles with `{0}` placeholders and colour tags. English
and Persian ship with the plugin, and `per-player-locale` serves each player in their own
client language when a matching bundle exists. Keys added by an update always resolve,
even when your on-disk message file predates them — your own edits are still preserved.

---

## Commands

Every command is registered at runtime. Only the primary aliases are listed.

### General

| Command | Aliases | Permission |
|---|---|---|
| `/essentials <reload\|version\|debug>` | `ess`, `essz` | `essentialsz.essentials` |
| `/gamemode <mode> [player]` | `gm`, `gmc`, `gms`, `gma`, `gmsp`, `gmt` | `essentialsz.gamemode` |

### Player

| Command | Aliases | Permission |
|---|---|---|
| `/fly [player] [on\|off]` | | `essentialsz.fly` |
| `/god [player] [on\|off]` | `godmode`, `tgm` | `essentialsz.god` |
| `/heal [player]` | | `essentialsz.heal` |
| `/feed [player]` | `eat` | `essentialsz.feed` |
| `/speed [type] <0-10> [player]` | `flyspeed`, `walkspeed` | `essentialsz.speed` |
| `/vanish [player] [on\|off]` | `v` | `essentialsz.vanish` |
| `/afk [player] [message]` | `away` | `essentialsz.afk` |

### Teleportation

| Command | Aliases | Permission |
|---|---|---|
| `/tpa <player>` | `call`, `tpask` | `essentialsz.tpa` |
| `/tpahere <player>` | | `essentialsz.tpahere` |
| `/tpaccept [player\|*]` | `tpyes` | `essentialsz.tpaccept` |
| `/tpdeny [player\|*]` | `tpno` | `essentialsz.tpdeny` |
| `/tp <player> [player]` · `/tp <x> <y> <z>` | `teleport`, `tele`, `tp2p` | `essentialsz.tp` |
| `/tphere <player>` | `s` | `essentialsz.tphere` |
| `/tppos <x> <y> <z> [yaw] [pitch] [world]` | | `essentialsz.tppos` |
| `/tpo <player> [player]` | | `essentialsz.tpo` |
| `/back` | `return` | `essentialsz.back` |
| `/spawn [player]` · `/setspawn` | | `essentialsz.spawn` · `.setspawn` |
| `/tpr` | `rtp`, `tprandom` | `essentialsz.tpr` |

### Homes, warps and kits

| Command | Aliases | Permission |
|---|---|---|
| `/home [player:][name]` | `homes` | `essentialsz.home` |
| `/sethome [[player:]name]` | `createhome` | `essentialsz.sethome` |
| `/delhome [player:]<name>` | `remhome`, `rmhome` | `essentialsz.delhome` |
| `/warp [name\|page] [player]` · `/warp admin` | `warps` | `essentialsz.warp` |
| `/setwarp <name>` | `createwarp` | `essentialsz.setwarp` |
| `/delwarp <name>` | `remwarp`, `rmwarp` | `essentialsz.delwarp` |
| `/kit [kit] [player]` · `/kit admin` | `kits` | `essentialsz.kit` |

### Economy — registered only while the economy module is enabled

| Command | Aliases | Permission |
|---|---|---|
| `/balance [player]` | `bal`, `money`, `seemoney` | `essentialsz.balance` |
| `/pay <player> <amount>` | | `essentialsz.pay` |
| `/balancetop [page]` | `baltop`, `rich`, `topmoney` | `essentialsz.balancetop` |
| `/mystatus` | `status` | `essentialsz.mystatus` |
| `/eco <give\|take\|set\|reset> <player> [amount]` | `economy` | `essentialsz.eco` |
| `/givemoney <player> <amount>` | `addmoney`, `addbalance` | `essentialsz.givemoney` |
| `/takemoney <player> <amount>` | `removemoney`, `removebalance` | `essentialsz.takemoney` |
| `/setmoney <player> <amount>` | `setbalance` | `essentialsz.setmoney` |

---

## Permissions

**66 permission nodes** are registered in code (plus **9** more while the economy is
enabled), all defaulting to operators — grant them with any permission plugin. Nothing is
declared in `plugin.yml`.

Naming follows a predictable pattern:

| Pattern | Meaning |
|---|---|
| `essentialsz.<command>` | Use the command |
| `essentialsz.<command>.others` | Target other players |
| `essentialsz.gamemode.<mode>` · `.all` | Switch into a specific mode / any mode |
| `essentialsz.speed.bypass` | Ignore the configured speed caps |
| `essentialsz.sethome.multiple.unlimited` | Ignore the home limit |
| `essentialsz.vanish.see` · `.onjoin` | See vanished players / vanish silently at login |
| `essentialsz.warp.overwrite` · `.admin` | Move existing warps / open the warp admin UI |
| `essentialsz.kits.<name>` | Claim a specific kit *(registered per kit)* |
| `essentialsz.category.<name>` | View a specific kit category |
| `essentialsz.kit.exemptdelay` | Ignore kit cooldowns |

Commands that replace a vanilla one also accept the vanilla permission:
`/gamemode` honours `pocketmine.command.gamemode.self` / `.other`, and `/tp` honours
`pocketmine.command.teleport.self` / `.other`.

---

## Configuration

`config.yml` is grouped by area and fully commented. The options at a glance:

| Group | Options |
|---|---|
| General | `locale`, `per-player-locale`, `debug`, `verbose-command-usages`, `disabled-commands` |
| Players | `max-fly-speed`, `max-walk-speed`, `remove-effects-on-heal`, `vanish-fake-quit-message`, `vanish-fake-join-message` |
| Teleports | `tpa-accept-cancellation`, `back-death-time-limit`, `max-homes`, `random-teleport.*` |
| Economy | `enabled`, `start-money`, `max-money`, `currency-symbol`, `decimals`, `allow-pay-offline`, `min-pay-amount` |
| RTL | `enabled`, `shape` |
| Storage | `provider`, `mysql.*`, `user-storage-key` |

Turning a module off is a single line, and the module then registers nothing at all:

```yaml
economy:
  enabled: false   # no economy commands, permissions, listeners or database

rtl:
  enabled: false   # no processor and no packet listener
```

---

## Developer API

Both optional modules return `null` while disabled — always null-check.

### Economy

```php
/** @var \Doma\EssentialsZ\EssentialsZ $essentials */
$economy = $essentials->getEconomy();
if($economy === null){
    return; // the economy module is disabled
}

$economy->createAccount($player->getName());
$economy->addBalance($player->getName(), 250.0);
$economy->transfer("alice", "bob", 50.0);

$balance = $economy->getBalance($player->getName()); // ?float
$rank    = $economy->getRank($player->getName());    // ?int, 1 = richest
echo $economy->formatMoney($balance ?? 0.0);         // "$1,234.50"
```

Every write fires a cancellable `BalanceChangeEvent`:

```php
public function onBalanceChange(BalanceChangeEvent $event) : void{
    if($event->getNewBalance() > 1_000_000){
        $event->cancel();
    }
}
```

### Right-to-left text

```php
$rtl = $essentials->getRtl();
if($rtl !== null && $rtl->hasRtl($text)){
    $text = $rtl->correct($text);
}
```

### Storage

Read and write your own fields on the shared player record, or plug in a custom backend
by implementing `storage\DataProvider`:

```php
$data = $essentials->getUsers()->getUser($player)->getData();
$data->setProperty("myplugin.points", 42);
$data->save();
```

---

## TODO
- ### Moderation
- ### Land (Claim) system
- ### Rank System or Group System maybe?
- ### Anything that you think is useful

---

## Contributing

Issues and pull requests are welcome. When adding a feature, please keep to the existing
conventions: commands and permissions registered in code, every player-facing string in
the message bundles (English **and** Persian), and one namespace per folder.
---
