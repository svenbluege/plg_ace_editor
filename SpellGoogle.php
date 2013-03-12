<?php

/**
 * @copyright	Copyright (C) 2012 Sven Bluege, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later
 */


if (!isset($_POST['lang']))
	die();

if (!isset($_POST['text']))
	die();

$lang = $_POST['lang'];	
$text = (array) $_POST['text'];

$result = check($lang, $text);

header('Content-type: application/json');
echo json_encode($result[0]);


function check($lang=en, $texts = "") {

	$response = array();

	foreach($texts as $text)
	{    
		$words = get_matches($lang, $text);

		$incorrect_words = array();

		foreach($words as $word)
		{
			$incorrect_words[] = mb_substr($text, $word[0], $word[1], 'utf-8');
		}

		$response[] = $incorrect_words;
	}

	return $response;

}


function get_matches($lang, $text)
{
	$text = html_entity_decode($text);
	$text = str_replace("<", " ", $text);

	$xml_response = '';
	$url = 'https://www.google.com/tbproxy/spell?lang='.$lang;

	$body = '<?xml version="1.0" encoding="utf-8" ?>';
	$body .= '<spellrequest textalreadyclipped="0" ignoredups="0" ignoredigits="1" ignoreallcaps="0">';
	$body .= '<text>'.$text.'</text></spellrequest>';

	if (!function_exists('curl_init'))
	{
		exit('Curl is not available');
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	$xml_response = curl_exec($ch);
	curl_close($ch);
	print_r($xml_response);
	$xml = simplexml_load_string($xml_response);

	$matches = array();

	foreach($xml->c as $word)
	{
		$matches[] = array(
			(int) $word->attributes()->o,
			(int) $word->attributes()->l,
			(int) $word->attributes()->s,
			(string) $word
		);
	}
	return $matches;
}
?>