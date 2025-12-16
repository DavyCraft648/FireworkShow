# <img src="https://github.com/DavyCraft648/FireworkShow/blob/main/icon.png" height="64" width="64"></img> FireworkShow

Bring automated firework displays to your PocketMine-MP server.

FireworkShow spawns fireworks at configured positions in worlds on a repeating schedule. It's ideal for nightly shows,
holidays, or automated decorations.

## Features

- Spawn fireworks automatically at configured positions per world
- Per-position options: enabled, night-only mode, spawn delay (ticks)
- Customizable explosions: type, colors, fade colors, twinkle, trail
- Add, remove, toggle positions from console or in-game via a simple UI
- Command name and aliases are configurable

## Installation

1. Drop the plugin .phar or plugin folder into your server's `plugins/` directory.
2. Start (or restart) the server. A default `config.yml` will be created if it doesn't exist.
3. Edit `config.yml` to set positions, command name, or explosion presets as needed.

### Requirements

- PocketMine-MP v5.34.0+
- PHP 8.2+


## Quick usage

Default command: `/fireworkshow` (can be changed in `config.yml`)

Permission: `fireworkshow.command.fireworkshow`

### Subcommands:

- `/fireworkshow list` — show configured positions for all worlds
- `/fireworkshow add <world> <x> <y> <z>` — add a new position (there's also an in-game UI)
- `/fireworkshow remove <world> <index>` — remove a position by its index
- `/fireworkshow toggle <world> <index>` — enable/disable a position

Notes:

- The command name and aliases are configurable in `config.yml`. The permission node is
  `fireworkshow.command.fireworkshow` by default.
- The plugin also provides a simple UI for adding, toggling, and removing positions when used in-game.

## Behavior details

- Each configured position runs on its own repeating task using the `spawnTick` interval (in ticks; 20 ticks ≈ 1
  second).
- If `nightOnly: true` for a position, fireworks spawn only while the world's time is in the night range.
- Fireworks spawn only when the chunk containing the position is loaded.
- If a position has an `explosions` list, those explosions are used deterministically. If omitted, the plugin will spawn
  randomized fireworks.
- Flight time (controlled by `flightTimeMultiplier`) determines how long the rocket entity will fly before exploding. See the "Flight time" section below for the exact formula and examples.

## Configuration

The plugin stores configuration in `config.yml`. A default file is generated on first run. Key sections:

- `command`: controls the registered command name, description, usage, and aliases.
- `positions`: list of configured firework positions. Each entry defines where and how fireworks spawn.

Example `positions` entries:

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
    flightTimeMultiplier: 1
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
    flightTimeMultiplier: 1
    explosions:
      - type: CREEPER
        colors:
          - LIME
        fade: []
        twinkle: false
        trail: false
```

### Position fields

- `worldName` (string): the world where the position is located.
- `x`, `y`, `z` (int/float): coordinates for the spawn location.
- `enabled` (bool): whether this position is active.
- `nightOnly` (bool): if true, spawn only during night.
- `spawnTick` (int): spawn interval in ticks (20 ticks ≈ 1 second).
- `flightTimeMultiplier` (int): multiplier that controls rocket flight duration before explosion. Valid range: 1–127. Default: 1
- `explosions` (optional array): explicit explosion definitions to use at this position.

### Flight time (how `flightTimeMultiplier` works)

When a firework is spawned the plugin computes a randomized duration in ticks using this formula:

random duration (ticks) = ((flightTimeMultiplier + 1) * 10) + random(0..12)

- Example: `flightTimeMultiplier = 1` -> ((1 + 1) * 10) + (0..12) = 20..32 ticks (≈ 1.0–1.6 seconds).
- Example: `flightTimeMultiplier = 5` -> ((5 + 1) * 10) + (0..12) = 60..72 ticks (≈ 3.0–3.6 seconds).
- Example: `flightTimeMultiplier = 127` -> ((127 + 1) * 10) + (0..12) = 1280..1292 ticks (≈ 64.0–64.6 seconds).

A larger `flightTimeMultiplier` makes rockets fly longer before exploding. The UI and config accept integer values; the in-game UI clamps the input to the 1–127 range.

### Explosion schema

Each explosion object supports:

- `type` (string|int): firework shape (case-insensitive name or numeric id). Common names: SMALL_BALL, LARGE_BALL, STAR,
  CREEPER, BURST.
- `colors` (array): dye color names (e.g., RED) or numeric color ids.
- `fade` (array, optional): dye color names or ids used for fade.
- `twinkle` (bool, optional): if true, the explosion twinkles.
- `trail` (bool, optional): if true, the explosion leaves a trail.

Notes on values:

- The plugin accepts both enum names and numeric IDs for colors and explosion types.
- Names are matched case-insensitively; separators like spaces, dashes, and underscores are ignored when matching.

## Troubleshooting

- No fireworks are appearing: check that the position's chunk is loaded and `enabled` is true.
- Night-only positions don't fire: verify the world's time and the `nightOnly` flag.
- Command not found: confirm the command name/aliases in `config.yml` and that you have the permission node.

## Contributing & Support

Found a bug or want a feature? Open an issue or submit a pull request.

## License

See the LICENSE file included with this project for license details.
