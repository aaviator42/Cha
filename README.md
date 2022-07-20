# Cha
A search engine for tagged data.

`v1.0`: `2022-07-20`

## What is this?

Cha performs searches on structured (tagged) data. What exactly does that mean? 

Here's an example - a stock photography database of images that have been tagged based on content:

```
img1: dog, puppy, animal, happy, nature
img2: cat, pet, kitten, playing, active
img3: tree, nature, green, earth
img4: building, grey, city, industrial
img5: sunset, happy, city, skyline, nature
img6: sky, sun, nature, blue, skyline
```

Cha allows you to query the index (for e.g., `city skyline` or `puppy playing with human`), and returns items based on decreasing relevance.

## How does it work?

1. Convert the query string into an array of keywords  
2. Drop keywords that we don't want to search for  
3. Add associated keywords we might also want to search for (synonyms and supplements)  
4. Use the following algorithm to calculate a _match score_ for each item in our database:  

    ```
    match_score(query, item) = n(keywords(query) ∩ tags(item))
    ```
    The match score is essentially the number of keywords that overlap with the item's tags.
  
5. Return items by decreasing match score

### Example:

Consider the following data index:

```
img1: sunset, happy, city, skyline, nature
img2: cat, pet, kitten, play, active
img3: tree, nature, green, earth
img4: building, grey, city, industrial
img5: dog, puppy, animal, happy, nature
img6: sky, sun, nature, blue, skyline
```

Here's how a search query would get processed at each step:

```
0. Query string:
    kiten and puppies in nature.

1. Converting query string to array:
    [and, in, kiten, nature, puppies]

2. Correcting misspellings:
    [and, in, kitten, nature, puppies]

3. Depluralizing:
    [and, in, kitten, nature, puppies, puppy]

4. Adding synonyms:
    [and, cat, dog, in, kitten, kitty, nature, puppies, puppy]

5. Adding supplements:
    [and, animal, cat, dog, domesticated, in, kitten, kitty, nature, pet, puppies, puppy]

6. Dropping insignificant words:
    [animal, cat, dog, domesticated, kitten, kitty, nature, pet, puppies, puppy]

7. Performing search:
    img5: 4 tag matches
    img2: 3 tag matches
    img3: 1 tag match
    img1: 1 tag match
    img6: 1 tag match
    img4: 0 tag matches
 ```

## Example usage

Here's the code for the example from the previous section.  

Obviously in real-world use cases the index and wordlists (thesaurus, supplements etc) would be read from a DB. 

The functions used are explained in the next section.

```php
<?php

require 'Cha.php';


//the data index
$index = [
          "img1" => ["sunset", "happy", "city", "skyline", "nature"],
          "img2" => ["cat", "pet", "kitten", "play", "active"],
          "img3" => ["tree", "nature", "green", "earth"],
          "img4" => ["building", "grey", "city", "industrial"],
          "img5" => ["dog", "puppy", "animal", "happy", "nature"],
          "img6" => ["sky", "sun", "nature", "blue", "skyline"]
         ];

//synonyms
$thesaurus = [
              ["big", "large", "huge"], 
              ["small", "tiny"],
              ["cat", "kitten", "kitty"],
              ["puppy", "dog"]
             ];

//words to drop
$droplist  = ["and", "of", "with", "in"];

//corrections for common misspellings
$corrections = [
                "kiten" => "kitten",
                "equiptment" => "equipment",
                "wierd" => "weird"
               ];

//word supplements
$supplements = [
                "dog"	=> ["animal", "pet", "domesticated"],
                "cat" => ["animal", "pet", "domesticated"],
                "red" => ["color"]
               ];


//the search string
$qString = "kiten and puppies in nature";

//convert string to array
$qArray = Cha\stringToArray($qString);

//correct misspellings 
$qArray = Cha\correctSpellings($qArray, $corrections);

//depluralize
$qArray = Cha\depluralize($qArray);

//add synonyms
$qArray = Cha\addSynonyms($qArray, $thesaurus);

//add supplements
$qArray = Cha\addSupplements($qArray, $supplements);

//drop insignificant words
$qArray = Cha\dropWords($qArray, $droplist);

//perform search
$results = Cha\search($qArray, $index);

```

## Functions

All functions are contained within the `Cha` namespace. 

### 1. `stringToArray(<query string>)`
Converts the string to an array of keywords ("query array") and returns it. The entire string is converted into lowercase, and whitespace and punctuation is stripped from it.

### 2. `correctSpellings(<query array>, $corrections)`

Correct common misspellings of words. Add words most relevant to your use case, although I guess you could load up [this](https://en.wikipedia.org/wiki/Wikipedia:Lists_of_common_misspellings) entire list.

Expected format of `$corrections`:

```php
$corrections = ["acheive" => "achieve", "abuot" => "about"];
```

### 3. `depluralize(<query array>)`
Adds depluralized forms of keywords to the array, but doesn't remove the originals. English specific, and far from perfect (it's a weird language!), but does work for most cases. Also does a couple other types of conversions that are technically not depluralizations, but are still handy. Will sometimes add nonsensical keywords to the array, but that doesn't really impact the search functionality.

Types of conversions:
 * cities → city
 * wives → wife
 * wolves → wolf
 * potatoes → potato
 * gasses → gas
 * matches → match
 * braces → brace
 * cars → car
 * playing → play
 * hated → hate
 * played → play

### 3. `addSynonyms(<query array>, $thesaurus)`
Add synonyms of the keywords to the array. If any of the words from a synonym group are contained in the array then all of them are added to it.

Expected format of `$thesaurus`:

```php
$thesaurus = [["big", "huge", "large"], ["small", "tiny"]];
```

### 4. `addSupplements(<query array>, $supplements)`
Unlike `addSynonyms()`, this is a one-way function. Use this when, for e.g., you want queries containing "cat" to always match with "domestic" and "pet", but not the other way around. 

Expected format of `$supplements`:

```php
$supplements = ["cat" => ["calilco", "animal", "pet", "domestic], "red" => ["color"], "laptop" => ["gadget", "tech"]];
```

### 5. `dropWords(<query array>, $droplist)`
Use this to drop insignificant words (like "and", "of", "with", etc) from the query array for a small performance bump. 

Expected format of `$droplist`:

```php
$droplist = ["of", "with", "along"];
```

### 6. `search(<query array>, $index)`
Uses the query array to search the index and returns an array sorted from most to least relevant. For optimal results, keep the tags in your index in singular form (hence the `depluralize()` function).

Expected index format:
```php
$index = [
          "img5" => ["dog", "puppy", "animal", "happy", "nature"],
          "img2" => ["cat", "pet", "kitten", "play", "active"],
          "img3" => ["tree", "nature", "green", "earth"],
          "img4" => ["building", "grey", "city", "industrial"],
          "img1" => ["sunset", "happy", "city", "skyline", "nature"],
          "img6" => ["sky", "sun", "nature", "blue", "skyline"]
         ];
```
Sample output:
```
array(6) {
  ["img5"]=>
  int(4)
  ["img2"]=>
  int(3)
  ["img3"]=>
  int(1)
  ["img1"]=>
  int(1)
  ["img6"]=>
  int(1)
  ["img4"]=>
  int(0)
}
```


------
Documentation updated: `2022-07-20`
