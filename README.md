# <img src="https://github.com/DavyCraft648/FireworkShow/blob/main/icon.png" height="64" width="64"></img> FireworkShow
Do you need something special to your server for a new year or at night?  Fireworks maybe?

This plugin will add a firework show to your server!

### Features
 - Spawning random type and color of fireworks
 - Editable fireworks' spawn positions and delay
 
### Config
```yaml
# FireworkShow plugin configuration file

#---------------------------------
# Do not change this
configVersion: FireworkShow v0.0.3
#---------------------------------

# Firework spawn delay in ticks (1 sec = 20 ticks)
#spawnTick: 40
spawnTick: 40

# The world name to spawn fireworks
#worldName: world
worldName: world

# Firework only spawn at night: true|false
#nightOnly: true
nightOnly: false

# Total positions
#positionCount: 8
positionCount: 8

# Positions
#
#pos1:    | 1   = Ordinal number
#  x: -12 | -12 = x position
#  y: 87  | 87  = y position
#  z: 29  | 29  = z position
pos1:
  x: -12
  y: 87
  z: 29
pos2:
  x: -29
  y: 87
  z: -12
pos3:
  x: 12
  y: 87
  z: -29
pos4:
  x: 29
  y: 87
  z: 12
pos5:
  x: 12
  y: 87
  z: 29
pos6:
  x: -29
  y: 87
  z: 12
pos7:
  x: -12
  y: 87
  z: -29
pos8:
  x: 29
  y: 87
  z: -12
```