<?php
/*
Cha.php
v1.1 - 2022-12-14

By @aaviator42
License: AGPLv3
*/

namespace Cha;

function stringToArray($qString){
	
	//if passed an array, return it unmodified
	if(is_array($qString)){
		return $qString;
	}
	
	//string to lowercase
	$qString = strtolower($qString);
	
	//strip whitespace
	$qString = preg_replace('!\s+!', ' ', $qString);
	//strip punctuation
	$qString = preg_replace("#[[:punct:]]#", "", $qString);

	//convert string to array
	$qArray = explode(" ", $qString);
	
	//remove empty elements
	$qArray = array_filter($qArray, fn($value) => !is_null($value) && $value !== '');
	
	return array_values($qArray);
}

function depluralize($qArray){
	//copy of query array
	$qArrayTemp = $qArray;
	
	foreach($qArray as $word){
		//cities -> city
		if(mb_substr($word, -3) === "ies"){
			$qArrayTemp[] = mb_substr($word, 0, -3) . 'y';
		} 
		//wives -> wife
		//wolves -> wolf
		else if (mb_substr($word, -3) === "ves"){
			$qArrayTemp[] = mb_substr($word, 0, -3) . 'f';
			$qArrayTemp[] = mb_substr($word, 0, -3) . 'fe';
		}
		//potatoes -> potato
		else if (mb_substr($word, -3) === "oes"){
			$qArrayTemp[] = mb_substr($word, 0, -3);
		}		
		//gasses -> gas
		else if (mb_substr($word, -4) === "sses"){
			$qArrayTemp[] = mb_substr($word, 0, -3);
		}
		//matches -> match
		//braces -> brace
		else if(mb_substr($word, -2) === "es"){
			$qArrayTemp[] = mb_substr($word, 0, -1);
			$qArrayTemp[] = mb_substr($word, 0, -2);
		} 
		//cars -> car
		else if (mb_substr($word, -1) === "s"){
			$qArrayTemp[] = mb_substr($word, 0, -1);
		}
		
		//not really a depluralisation, but:
		//playing -> play
		else if (mb_substr($word, -3) === "ing"){
			$qArrayTemp[] = mb_substr($word, 0, -3);
		}
		//played -> play
		//hated -> hate
		else if (mb_substr($word, -2) === "ed"){
			$qArrayTemp[] = mb_substr($word, 0, -2);
			$qArrayTemp[] = mb_substr($word, 0, -1);
		}
		
	}
	
	return array_unique($qArrayTemp);
}
	

function addSynonyms($qArray, $thesaurus){
	//copy of query array
	$qArrayTemp = $qArray;
	
	//run for each word in query array
	foreach($qArray as $word){
		foreach($thesaurus as $group){
			if(in_array($word, $group)){
				//word is in current synonym group
				//add all synonyms to query array
				$qArrayTemp = array_merge($qArrayTemp, $group);
				//skip to next word
				continue 2;
			}
		}
	}
	
	//return query string with synonyms added
	return array_unique($qArrayTemp);
}

function addSupplements($qArray, $supplements){
	//copy of query array
	$qArrayTemp = $qArray;
	
	
	//run for each word in query array
	foreach($qArray as $word){
		if(isset($supplements[$word])){
			$qArrayTemp = array_merge($qArrayTemp, $supplements[$word]);
		}
	}
	
	//return query string with supplements added
	return array_unique($qArrayTemp);
}

function dropWords($qArray, $droplist){
	//remove words from droplist from query array
	$qArray = array_diff($qArray, $droplist);
	return $qArray;
}

function correctSpellings($qArray, $corrections){
	//copy of query array
	$qArrayTemp = $qArray;
	$correctedWords = array();
	
	foreach($qArray as $word){
		if(isset($corrections[$word])){
			//add correct spelling to array
			$qArrayTemp[] = $corrections[$word];
			$correctedWords[] = $word;
		}
	}
	
	//remove misspelled words 
	$qArrayTemp = array_diff($qArrayTemp, $correctedWords);
	
	return array_unique($qArrayTemp);
}



function search($qArray, $index, $confidence = 100){
	$results = array();
	
	//no fuzziness
	if($confidence >= 100){
		//compare tags with every item in index
		foreach($index as $item => $tags){
			$results[$item] = count(array_intersect($qArray, $tags));
		}
		arsort($results);
		return $results;
	}
	
	if($confidence < 0){
		$confidence = 0;
	}
	
	//compare tags with every item in index
	foreach($index as $item => $tags){
		foreach($qArray as $qWord){
			foreach($tags as $tag){
				$similarity;
				similar_text($tag, $qWord, $similarity);
				if($similarity >= $confidence){
					if(isset($results[$item])){
						$results[$item]++;
					} else {
						$results[$item] = 1;
					}
				}
			}
		}
	}
		
	arsort($results);
	return $results;

}
