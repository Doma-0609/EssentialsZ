# EssentialsZ — Permissions

Every permission node below is registered **in code at runtime** — nothing is
declared in `plugin.yml`. All nodes share the `essentialsz.` prefix.

**Defaults**

- Most nodes **default to operators**. Grant them to players or groups with any
  permissions plugin.
- The four everyday **land** commands (`essentialsz.land`, `essentialsz.startp`,
  `essentialsz.endp`, `essentialsz.landsell`) **default to everyone**.
- The land **bypass** nodes stay operator-only.

**Module gating**

- Economy nodes register only when `economy.enabled: true`.
- Land nodes register only when `land.enabled: true`.

Commands that replace a vanilla one also accept the matching vanilla permission —
see [Vanilla fallback permissions](#vanilla-fallback-permissions).

---

## Commands & aliases

Each command answers to its primary label **and** every alias listed here (most
also have an `e`-prefixed twin to avoid clashing with other plugins). The
permission is tied to the command, so any alias needs the same node.

| Command | Aliases |
|---|---|
| `/gamemode` | `/gm` `/gmc` `/gms` `/gma` `/gmsp` `/gmv` `/gmt` `/creative` `/survival` `/adventure` `/spectator` `/sp` `/spec` `/creativemode` `/survivalmode` `/adventuremode` (+ `e`-prefixed forms) |
| `/essentials` | `/ess` `/essz` `/essversion` `/essentialsz` (+ `e`-prefixed forms) |
| `/fly` | `/efly` |
| `/god` | `/godmode` `/tgm` (+ `e`-prefixed forms) |
| `/heal` | `/eheal` |
| `/feed` | `/eat` (+ `e`-prefixed forms) |
| `/speed` | `/fspeed` `/wspeed` `/flyspeed` `/walkspeed` (+ `e`-prefixed forms) |
| `/scale` | `/size` (+ `e`-prefixed forms) |
| `/spider` | `/climb` (+ `e`-prefixed forms) |
| `/repair` | `/fix` (+ `e`-prefixed forms) |
| `/vanish` | `/v` (+ `e`-prefixed forms) |
| `/afk` | `/away` (+ `e`-prefixed forms) |
| `/tpa` | `/call` `/tpask` (+ `e`-prefixed forms) |
| `/tpaccept` | `/tpyes` (+ `e`-prefixed forms) |
| `/tpahere` | `/etpahere` |
| `/tpdeny` | `/tpno` (+ `e`-prefixed forms) |
| `/back` | `/return` (+ `e`-prefixed forms) |
| `/spawn` | `/espawn` |
| `/setspawn` | `/esetspawn` |
| `/tpr` | `/rtp` `/tprandom` (+ `e`-prefixed forms) |
| `/tp` | `/teleport` `/tele` `/tp2p` (+ `e`-prefixed forms) |
| `/tphere` | `/s` `/etphere` |
| `/tppos` | `/etppos` |
| `/tpo` | `/etpo` |
| `/home` | `/homes` (+ `e`-prefixed forms) |
| `/sethome` | `/createhome` (+ `e`-prefixed forms) |
| `/delhome` | `/remhome` `/rmhome` (+ `e`-prefixed forms) |
| `/warp` | `/warps` (+ `e`-prefixed forms) |
| `/setwarp` | `/createwarp` (+ `e`-prefixed forms) |
| `/delwarp` | `/remwarp` `/rmwarp` (+ `e`-prefixed forms) |
| `/kit` | `/kits` (+ `e`-prefixed forms) |
| `/time` | `/day` `/night` (+ `e`-prefixed forms) |
| `/balance` | `/bal` `/money` `/seemoney` (+ `e`-prefixed forms) |
| `/pay` | `/epay` |
| `/balancetop` | `/baltop` `/rich` `/topmoney` (+ `e`-prefixed forms) |
| `/eco` | `/economy` (+ `e`-prefixed forms) |
| `/givemoney` | `/addmoney` `/addbalance` (+ `e`-prefixed forms) |
| `/takemoney` | `/removemoney` `/removebalance` (+ `e`-prefixed forms) |
| `/setmoney` | `/setbalance` (+ `e`-prefixed forms) |
| `/mystatus` | `/status` (+ `e`-prefixed forms) |
| `/land` | `/claim` `/eland` `/eclaim` |
| `/startp` | `/setpos1` `/estartp` |
| `/endp` | `/setpos2` `/eendp` |
| `/landsell` | `/elandsell` |

In the permission tables below the **Command** column shows the primary label
(and its most useful aliases); any alias from this section works the same.

---

## General

| Permission | Default | Command | What it does |
|---|---|---|---|
| `essentialsz.gamemode` | op | `/gamemode` | Use the command |
| `essentialsz.gamemode.all` | op | `/gamemode` | Change into **any** game mode |
| `essentialsz.gamemode.survival` | op | `/gamemode` · `/gms` | Change into survival |
| `essentialsz.gamemode.creative` | op | `/gamemode` · `/gmc` | Change into creative |
| `essentialsz.gamemode.adventure` | op | `/gamemode` · `/gma` | Change into adventure |
| `essentialsz.gamemode.spectator` | op | `/gamemode` · `/gmsp` · `/gmv` | Change into spectator |
| `essentialsz.gamemode.others` | op | `/gamemode` | Change another player's game mode |
| `essentialsz.essentials` | op | `/essentials` | Reload, version and debug toggle |

---

## Player

| Permission | Default | Command | What it does |
|---|---|---|---|
| `essentialsz.fly` | op | `/fly` | Toggle your own flight |
| `essentialsz.fly.others` | op | `/fly <player>` | Toggle flight for others |
| `essentialsz.god` | op | `/god` | Toggle your own god mode |
| `essentialsz.god.others` | op | `/god <player>` | Toggle god mode for others |
| `essentialsz.god.pvp` | op | `/god` | Still able to hit players while in god mode |
| `essentialsz.heal` | op | `/heal` | Heal yourself |
| `essentialsz.heal.others` | op | `/heal <player>` | Heal other players |
| `essentialsz.feed` | op | `/feed` | Feed yourself |
| `essentialsz.feed.others` | op | `/feed <player>` | Feed other players |
| `essentialsz.speed` | op | `/speed` · `/fspeed` · `/wspeed` | Change your move/fly speed |
| `essentialsz.speed.others` | op | `/speed … <player>` | Change another player's speed |
| `essentialsz.speed.fly` | op | `/speed fly` · `/fspeed` · `/flyspeed` | Change the **fly** speed |
| `essentialsz.speed.walk` | op | `/speed walk` · `/wspeed` · `/walkspeed` | Change the **walk** speed |
| `essentialsz.speed.bypass` | op | `/speed` `/fspeed` `/wspeed` | Ignore the configured speed caps |
| `essentialsz.scale` | op | `/scale` | Resize your own body |
| `essentialsz.scale.others` | op | `/scale <size> <player>` | Resize other players |
| `essentialsz.spider` | op | `/spider` | Toggle wall climbing on yourself |
| `essentialsz.spider.others` | op | `/spider <player>` | Toggle wall climbing for others |
| `essentialsz.repair` | op | `/repair` | Repair the durable item in your hand |
| `essentialsz.vanish` | op | `/vanish` | Toggle your own vanish |
| `essentialsz.vanish.others` | op | `/vanish <player>` | Toggle vanish for others |
| `essentialsz.vanish.see` | op | — | See players who are vanished |
| `essentialsz.vanish.onjoin` | op | — | Vanish silently on join (needs `auto-vanish: true`) |
| `essentialsz.vanish.pvp` | op | — | Still able to hit players while vanished |
| `essentialsz.afk` | op | `/afk` | Toggle your own AFK |
| `essentialsz.afk.others` | op | `/afk <player>` | Toggle AFK for others |
| `essentialsz.afk.message` | op | `/afk <message>` | Set an AFK reason |

---

## Teleportation

| Permission | Default | Command | What it does |
|---|---|---|---|
| `essentialsz.tpa` | op | `/tpa` | Request to teleport to a player |
| `essentialsz.tpaccept` | op | `/tpaccept` | Accept teleport requests |
| `essentialsz.tpahere` | op | `/tpahere` | Request a player teleport to you |
| `essentialsz.tpdeny` | op | `/tpdeny` | Deny teleport requests |
| `essentialsz.back` | op | `/back` | Return to your last death location |
| `essentialsz.back.others` | op | `/back <player>` | Return others to their death location |
| `essentialsz.spawn` | op | `/spawn` | Teleport to spawn |
| `essentialsz.spawn.others` | op | `/spawn <player>` | Send others to spawn |
| `essentialsz.setspawn` | op | `/setspawn` | Set the server spawn |
| `essentialsz.tpr` | op | `/tpr` | Random teleport |
| `essentialsz.tpr.others` | op | `/tpr … <player>` | Randomly teleport others |
| `essentialsz.tp` | op | `/tp` | Teleport to a player |
| `essentialsz.tp.others` | op | `/tp <player> <player>` | Teleport other players |
| `essentialsz.tp.position` | op | `/tp <x> <y> <z>` | Teleport to coordinates |
| `essentialsz.tphere` | op | `/tphere` | Teleport a player to you |
| `essentialsz.tppos` | op | `/tppos` | Teleport to coordinates (with yaw/pitch/world) |
| `essentialsz.tpo` | op | `/tpo` | Teleport, reaching vanished players |

---

## Homes & Warps

| Permission | Default | Command | What it does |
|---|---|---|---|
| `essentialsz.home` | op | `/home` | Teleport to your homes |
| `essentialsz.home.others` | op | `/home <player>:<name>` | Teleport to another player's home |
| `essentialsz.sethome` | op | `/sethome` | Set homes |
| `essentialsz.sethome.others` | op | `/sethome <player>:<name>` | Set another player's home |
| `essentialsz.sethome.multiple.unlimited` | op | `/sethome` | Ignore the `max-homes` limit |
| `essentialsz.delhome` | op | `/delhome` | Delete homes |
| `essentialsz.delhome.others` | op | `/delhome <player>:<name>` | Delete another player's home |
| `essentialsz.warp` | op | `/warp` | Warp to a location |
| `essentialsz.warp.list` | op | `/warp` | List warps |
| `essentialsz.warp.others` | op | `/warp <name> <player>` | Warp other players |
| `essentialsz.warp.admin` | op | `/warp admin` | Open the warp admin UI |
| `essentialsz.setwarp` | op | `/setwarp` | Create a warp |
| `essentialsz.warp.overwrite` | op | `/setwarp` | Move an existing warp onto a new location |
| `essentialsz.delwarp` | op | `/delwarp` | Delete a warp |

---

## Kits

| Permission | Default | Command | What it does |
|---|---|---|---|
| `essentialsz.kit` | op | `/kit` | Open kits / claim a kit |
| `essentialsz.kit.others` | op | `/kit <kit> <player>` | Give a kit to another player |
| `essentialsz.kit.admin` | op | `/kit admin` | Open the kit admin UI |
| `essentialsz.kit.exemptdelay` | op | `/kit` | Ignore kit cooldowns |
| `essentialsz.category` | op | `/kit` | View **every** kit category |
| `essentialsz.category.<name>` | op | `/kit` | View the locked `<name>` category *(registered per locked category)* |
| `essentialsz.kits.<name>` | op | `/kit <name>` | Claim the `<name>` kit *(registered per kit)* |

---

## Time

| Permission | Default | Command | What it does |
|---|---|---|---|
| `essentialsz.time` | op | `/time` | Query the world time |
| `essentialsz.time.set` | op | `/time set\|add\|stop\|start` | Change or freeze/resume the world time |
| `essentialsz.time.world.all` | op | `/time … <world>` | Change the time in **every** world |
| `essentialsz.time.world.<world>` | op | `/time … <world>` | Change the time in one world *(only checked when `world-time-permissions: true`; spaces become underscores)* |

---

## Economy *(only when `economy.enabled: true`)*

| Permission | Default | Command | What it does |
|---|---|---|---|
| `essentialsz.balance` | op | `/balance` | View your balance |
| `essentialsz.balance.others` | op | `/balance <player>` | View another player's balance |
| `essentialsz.pay` | op | `/pay` | Pay another player |
| `essentialsz.balancetop` | op | `/balancetop` | View the richest players |
| `essentialsz.mystatus` | op | `/mystatus` | View your wealth rank and share |
| `essentialsz.eco` | op | `/eco` | Admin: give/take/set/reset balances |
| `essentialsz.givemoney` | op | `/givemoney` | Add money to a balance |
| `essentialsz.takemoney` | op | `/takemoney` | Take money from a balance |
| `essentialsz.setmoney` | op | `/setmoney` | Set a balance |

---

## Land *(only when `land.enabled: true`)*

| Permission | Default | Command | What it does |
|---|---|---|---|
| `essentialsz.land` | **everyone** | `/land` | Use the land command and its subcommands |
| `essentialsz.startp` | **everyone** | `/startp` | Mark the first corner of a claim |
| `essentialsz.endp` | **everyone** | `/endp` | Mark the second corner of a claim |
| `essentialsz.landsell` | **everyone** | `/landsell` | Sell a claim you own |
| `essentialsz.land.bypass` | op | — | Build in and interact with **any** claim |
| `essentialsz.land.limit.bypass` | op | `/land buy` | Ignore the per-player claim limit |

---

## Vanilla fallback permissions

These commands replace their vanilla counterpart and also accept the built-in
PocketMine permission, so existing rank setups keep working without change:

| Command | Vanilla permissions honoured |
|---|---|
| `/gamemode` | `pocketmine.command.gamemode.self` · `pocketmine.command.gamemode.other` |
| `/tp` | `pocketmine.command.teleport.self` · `pocketmine.command.teleport.other` |
| `/time` | `pocketmine.command.time.query` · `pocketmine.command.time.add` · `pocketmine.command.time.set` |
