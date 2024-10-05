<?php

	require_once("hub_tools.php");

	// Sample DATA
	$sample_hex_data = "frame=3C3D3E860523353537344243235343503930233139234241543A313030234E4F4953453A302E30302354433A32362E33312348554D3A32392E3223505245533A37353432312E383023";

	// Get current timestamo
	$curr_timestamp = getTimestampFromNTP();

	// Save raw HEX data
	$raw_data_resp = insertRawData($curr_timestamp, $sample_hex_data);
	echo $raw_data_resp;
	echo "\n". PHP_EOL;

	// Decode raw HEX and save it
	$dec_data_resp = decodenInsertData($curr_timestamp, $sample_hex_data);
	echo $dec_data_resp;
	echo "\n". PHP_EOL;

	// If both inserts were succesful
	if ($raw_data_resp && $dec_data_resp){
		echo "Raw hex data and decoded hex data were succesfully saved\n". PHP_EOL;
	}