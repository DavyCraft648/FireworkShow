# <img src="https://github.com/DavyCraft648/FireworkShow/blob/main/icon.png" height="64" width="64"></img> FireworkShow
Do you need something special to your server for a new year or at night?  Fireworks maybe?

This plugin will add a firework show to your server!

### Features
 - Spawning random type and color of fireworks
 - Editable fireworks' spawn positions and delay
 
### Config
```yaml
# FireworkShow plugin configuration file

# Command configuration
# Allows changing the command name, description, usage and aliases without editing plugin code.
# Aliases should be a list (e.g. [fwshow, fws]).
command:
  name: fireworkshow
  description: "Manage firework shows"
  usage: "/fireworkshow"
  aliases: [fwshow, fws]

# Positions list - each entry can override worldName, nightOnly, enabled, spawnTick
# spawnTick is in ticks (20 ticks = ~1 second)
# explosions is optional and is a user-friendly list. Fields:
#   type: name or integer (e.g. SMALL_BALL or 0)
#     possible names: SMALL_BALL, LARGE_BALL, STAR, CREEPER, BURST (case-insensitive)
#   colors: array of dye color names or ids (strings like RED, BLUE or integers)
#   fade: optional array of dye color names or ids for fade
#   twinkle: optional boolean
#   trail: optional boolean
# Example:
# positions:
#   - worldName: world
#     x: -12
#     y: 87
#     z: 29
#     enabled: true
#     nightOnly: false
#     spawnTick: 40
#     explosions:
#       - type: SMALL_BALL
#         colors: [RED, BLUE]
#         fade: [YELLOW]
#         twinkle: false
#         trail: true
#   - worldName: world_nether
#     x: 10
#     y: 80
#     z: -5
#     enabled: false

positions:
  - worldName: world
    x: -12
    y: 87
    z: 29
    enabled: true
    nightOnly: false
    spawnTick: 40
    explosions:
      - type: SMALL_BALL
        colors: [RED, BLUE]
        fade: [YELLOW]
        twinkle: false
        trail: true

  - worldName: world_nether
    x: 10
    y: 80
    z: -5
    enabled: true
    nightOnly: true
    spawnTick: 60
```