# GoogleShopping Category Update Script

This shell script can help you update your GoogleShopping categories.
You need to run the script manually when updating to model version 0.2.4 .

## Preparation

First you have to prepare the category entries. The script will ignore all 
positive values to prepend double mapping.

```
php -f googleshopping_taxonomy_mapping.php -- --prepare true
```

You can also specify a Store ID, if you do not want to prepare all stores at once:

```
php -f googleshopping_taxonomy_mapping.php -- --prepare true --store 1
```

Please note, that the preparation method will "unprepare" if you run it twice 
(negative values will get positive on second run).

## Mapping

This compares old and new categories for exact string matching and creates a map
file. You can add your own mappings to the generated file.

```
php -f googleshopping_taxonomy_mapping.php -- --createmap --oldcat googleshopping_taxonomy_mapping/taxonomy.de_DE.txt --newcat googleshopping_taxonomy_mapping/taxonomy-with-ids.de_DE.txt > googleshopping_taxonomy_mapping/map_de_DE.txt
php -f googleshopping_taxonomy_mapping.php -- --categoryidmap googleshopping_taxonomy_mapping/map_de_DE.txt --lang de_DE --store 1
```

## Perform category update

```
php -f googleshopping_taxonomy_mapping.php -- --categoryidmap googleshopping_taxonomy_mapping/map_de_DE.txt --lang de_DE
```