##PokeScript
*Be alerted when a pokemon pop near your place!*

This is a tiny php script that use pokevision service to scan and read pokemons positions.
If mplayer is install, then pokesound.mp3 will be play each time a new pokemon appear.

##Usage
Run 
```
php pokescript.php
```
to print the list of pokemons near to you. 

Sample output:

```
6m        | Spearow    | new       | 824s   |
14m       | Pidgeotto  | old       | 53s    |
29m       | Pidgey     | old       | 180s   |
```

Use watch command to automaticly refresh the scan:
```
watch -n 60 php pokescript.php
```
