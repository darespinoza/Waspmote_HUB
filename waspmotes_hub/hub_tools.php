<?php
	// Constants to save data on Meshliums mirror table on your server
	const FRAME_TYPE = 0;
	const PARSER_TYPE = 12;
	const MESHLIUM_ID = "dario_waspmotehub";

	// HEXADECIMAL and ASCII data separators
	const HEX_SEPARATOR = "23";
	const ASC_SEPARATOR = "#";

	// Sensor and value separator
	const SENSOR_SEPARATOR = ":";

	// NTP server time parameters
	const TIMEZONE = 'America/Guayaquil';
	const API_URL = "http://worldtimeapi.org/api/timezone/";

	// Max and min lenght for request string
	const MAX_STR_LENGHT = 200;
	const MIN_STR_LENGHT = 0;

	// Database connection
	require_once("db_connection.php");

	// Function to check if string lenght is between min and max
	function checkStringLengh($raw_hex){
		try{
			if(strlen($raw_hex) >= MIN_STR_LENGHT && strlen($raw_hex) <= MAX_STR_LENGHT){
				return true;
			}else{
				return false;
			}
		}catch(Exception $exception){
			return false;
		}
	}

	/** Function to perform a request to NTP server to get current timestamp
	 * If NTP API is not available, the function will return system's current timestamp
	 * */
	function getTimestampFromNTP(){
		try{
			// Perform request to API
			$api_url = API_URL . TIMEZONE;
			$response = file_get_contents($api_url);

			// Check if response was succesful
			if ($response !== false) {
			    // Decode JSON response
				$data = json_decode($response, true);

			    // Check if timestamp is in response
				if (isset($data['datetime'])) {
			        // Convert datetime into desired format 'Y-m-d H:i:s'
					$datetime = $data['datetime'];
					$formatted_time = date('Y-m-d H:i:s', strtotime($datetime));

					// Return timestamp
					return $formatted_time;
				} else {
					// echo "Error: Unable to get timestamp.\n". PHP_EOL;
					return date('Y-m-d H:i:s');
				}
			} else {
				// echo "Error: Unable to connect to API.\n". PHP_EOL;
				return date('Y-m-d H:i:s');
			}
		}catch(Exception $exception){
			// echo ("Can't get timetamp from NTP server. ". $exception->getMessage(). PHP_EOL);
			return date('Y-m-d H:i:s');
		}
	}

	// Function to decode HEX data
	function hexToAsciiWasp($raw_hex, $hex_sep){
		try{
			// Split raw data using HEX separator
			$arrayHexData = explode($hex_sep, $raw_hex);

			// Ignore array's first element, as it usually causes and error on ASCII conversion
			$slice = array_slice($arrayHexData, 1);
			
			// Join sliced hex string using HEX separator
			$resultado = implode($hex_sep, $slice);
			$decoded_string = hex2bin($resultado);

			return $decoded_string;
		}catch(Exception $exception){
			// echo ("Cant decode HEX string." . $exception->getMessage(). PHP_EOL);
			return "";
		}
	}

	/** Function to save raw hex data from Waspmotes in "raw_sensor_parser" table
	 * I decided to require the timestamp as a parameter for the function, because it will be needed for insertion in "sensor_parser_mirror" too
	 * */
	function insertRawData ($insert_timestamp, $raw_hex){
		$conn = null;
		$insert_stmt = null;

		try{
			// Connect to database
			$conn = connect_to_db();

			// Decode HEX raw data
			$decoded_string = hexToAsciiWasp($raw_hex, HEX_SEPARATOR);

			// Prepare INSERT statement
			$insert_stmt = mysqli_prepare($conn, "INSERT INTO my_meshliumdb.raw_sensor_parser (timestamp, raw_hex, decoded_hex) VALUES (?, ?, ?)");

			// Check if prepared statement was prepared correctly
			if ($insert_stmt){
				// Bind three string parameters
				mysqli_stmt_bind_param($insert_stmt, "sss", $insert_timestamp, $raw_hex, $decoded_string);

				// Execute prepared statement
				if (mysqli_stmt_execute($insert_stmt)) {
					return true;
				} else {
					return false;
				}
			}else{
				return false;
			}

		}catch(Exception $exception){
			// echo ("Unable to insert data on Meshliums mirror table. " . $exception->getMessage(). PHP_EOL);
			return false;
		}finally{
			// Close statement
			if ($insert_stmt) {
				mysqli_stmt_close($insert_stmt);
			}

			// Close database connection
			if ($conn) {
				mysqli_close($conn);
			}
		}
	}

	/** Function to decode raw hex data and save it in "sensor_parser_mirror" table
	 * Expected parameters on HEX string are:
	 * 1. id_secret
	 * 2. id_wasp
	 * 3. frame_number
	 * 4. sensor=value
	 * 5. sensor=value
	 * 6. ...
	 * 
	 * */
	function decodenInsertData($insert_timestamp, $raw_hex){
		$conn = null;
		$insert_stmt = null;
		$insert_flag = false;

		try{
			// Connect to database
			$conn = connect_to_db();

			// Decode HEX raw data
			$decoded_string = hexToAsciiWasp($raw_hex, HEX_SEPARATOR);

			// Explode decoded string
			$param_array = explode(ASC_SEPARATOR, $decoded_string);

			// Check if array as at leat one sensor and value
			if (count($param_array) > 3){
				// First element is id_secret
				$id_secret = &$param_array[0];

				// Second element is id_wasp
				$id_wasp = &$param_array[1];

				// Third element is frame_number
				$frame_number = &$param_array[2];

				// Prepare INSERT statement
				$insert_stmt = mysqli_prepare($conn, "INSERT INTO my_meshliumdb.sensor_parser_mirror (id_wasp, id_secret, frame_type, frame_number, sensor, value, timestamp, parser_type, MeshliumID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

				// Check if prepared statement was prepared correctly
				if ($insert_stmt){

					// Iterate with sensors and their values
					for ($i = 3; $i < count($param_array); $i++) {
						try{
							// Split sensor and value
							$sensor_now = explode(SENSOR_SEPARATOR, $param_array[$i]);

							// Check if sensor and value exists
							if(count($sensor_now) == 2){
								// Save array elements in variables
								$sensor = $sensor_now[0];
								$value = $sensor_now[1];

								// Assign CONSTANTS to variables, because mysqli_stmt_bind_param requirement
								$frame_type = FRAME_TYPE;
								$parser_type = PARSER_TYPE;
								$meshlium_id = MESHLIUM_ID;

								// Bind parameters
								mysqli_stmt_bind_param($insert_stmt, "ssiisssis", $id_wasp, $id_secret, $frame_type, $frame_number, $sensor, $value, $insert_timestamp, $parser_type, $meshlium_id);

								// Execute prepared statement
								if (mysqli_stmt_execute($insert_stmt)) {
									$insert_flag = $insert_flag || true;
								} else {
									$insert_flag = $insert_flag || false;
								}
							} else {
								$insert_flag = $insert_flag || false;
							}
						}catch(Exception $exc_ins){
							// echo("Error: Saving $param_array[$i] data.". $exc_ins->getMessage(). PHP_EOL);
						}
					}

					// If at least one sensor and its value were inserted, this flag will be true
					return $insert_flag;
				}else{
					return false;
				}
			}else{
				// echo ("Required parameters to save data, not found". PHP_EOL);
				return false;
			}
		}catch(Exception $exception){
			// echo ("Unable to insert data on sensor_parser_mirror. " . $exception->getMessage(). PHP_EOL);
			return false;
		}finally{
			// Close statement
			if ($insert_stmt) {
				mysqli_stmt_close($insert_stmt);
			}

			// Close database connection
			if ($conn) {
				mysqli_close($conn);
			}
		}
	}
