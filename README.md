# <img src="https://github.com/DavyCraft648/FireworkShow/blob/main/icon.png" height="64" width="64"></img> FireworkShow

Bring fireworks show to your PocketMine-MP server.

FireworkShow spawns fireworks at configurable positions in worlds on a repeating schedule. Use it for nightly shows, holiday events, or automated decorations.

## Key highlights
- Spawn fireworks automatically at configured positions per world
- Per-position options: enabled, night-only mode, spawn delay (ticks)
- Fine-grained explosion definitions: type, colors, fade colors, twinkle, trail
- Add/remove/toggle positions from console or in-game via a simple UI

## Requirements
- PocketMine-MP v5.34.0+
- PHP 8.2+

## Commands and permissions
The command name and aliases are configurable in the plugin config. Defaults:
- Command: `/fireworkshow`
- Permission: `fireworkshow.command.fireworkshow`

Subcommands:
- `list` — show configured positions
- `add <world> <x> <y> <z>` — add a new position (in-game UI is available)
- `remove <world> <index>` — remove a position by index
- `toggle <world> <index>` — enable/disable a position

## Behavior notes
- Each configured position runs on its own repeating task using the `spawnTick` value (in ticks; 20 ticks ≈ 1 second).
- If a position is marked `nightOnly: true`, fireworks will only spawn while the world's time is in the night range.
- Fireworks are only spawned when the chunk containing the position is loaded.

## Configuration
The configuration is YAML and stored in `config.yml` (a default is created on first run). The useful blocks are `command` and `positions`.

- command: controls the registered command name, description, usage, and aliases.
- positions: a list of positions. Each position can optionally define `explosions` for deterministic fireworks.

Example config:

```yaml
command:
  name: fireworkshow
  description: Manage firework shows
  usage: /fireworkshow
  aliases:
    - fwshow
    - fws

positions:
  - worldName: world
    x: -385
    y: 67
    z: -24
    enabled: true
    nightOnly: false
    spawnTick: 40
    explosions:
      - type: SMALL_BALL
        colors:
          - LIGHT_BLUE
        fade: []
        twinkle: true
        trail: false
  - worldName: world
    x: -390
    y: 66
    z: -20
    enabled: true
    nightOnly: false
    spawnTick: 40
    explosions:
      - type: CREEPER
        colors:
          - LIME
        fade: []
        twinkle: false
        trail: false
  - worldName: world
    x: -376
    y: 66
    z: -32
    enabled: true
    nightOnly: false
    spawnTick: 40
    explosions:
      - type: BURST
        colors:
          - MAGENTA
        fade:
          - BLUE
        twinkle: true
        trail: false
  - worldName: world
    x: -377
    y: 66
    z: -17
    enabled: true
    nightOnly: false
    spawnTick: 40
    explosions:
      - type: STAR
        colors:
          - LIGHT_BLUE
        fade:
          - BLUE
          - CYAN
        twinkle: false
        trail: true
```

### Explosions schema
- type: firework shape name (case-insensitive) or numeric id. Possible shape names include SMALL_BALL, LARGE_BALL, STAR, CREEPER, BURST.
- colors: array of dye color names or numeric ids
- fade: optional array of dye color names or ids used for fade
- twinkle: optional boolean
- trail: optional boolean

### Notes on color and type resolution
- The plugin accepts both enum names (e.g., `RED`) and numeric IDs for colors and explosion types.
- Names are matched case-insensitively and common separators (space, dash, underscore) are ignored when matching color names.

## License
This project is distributed under the terms in the LICENSE file in this repository.

If you find an issue or want a feature, please open an issue or a pull request. Contributions are welcome.
