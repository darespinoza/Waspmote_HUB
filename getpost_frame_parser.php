<?php
    // Import functions
    require_once("waspmotes_hub/hub_tools.php");
    require_once("waspmotes_hub/hub_validations.php");

    try{
        // Check if request uses POST method
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            // Validate and sanitize data
            $sanitized_post = validateAndSanitize($_POST);

            // There's only one parameter expected -> frame
            if(count($sanitized_post) == 1){

                // Get frame data
                $frame = isset($sanitized_post[FRAME_KEY]) ? $sanitized_post[FRAME_KEY] : null;

                // Check that frame is not empty
                if (!empty($frame)) {

                    // Check if frame data is a string
                    if(is_string($frame)){

                        // Check if frame data string is inside min and max lenght
                        if(checkStringLengh($frame)){

                            // Get current timestamp from NTP Server
                            $curr_timestamp = getTimestampFromNTP();

                            // OR get timestamp from server
                            // $curr_timestamp = date('Y-m-d H:i:s');

                            // Save raw data
                            $raw_data_resp = insertRawData($curr_timestamp, $frame);

                            // Decode and save data on Meshliums mirror table on your own server
                            $dec_data_resp = decodenInsertData($curr_timestamp, $frame);

                            // Check if both inserts where sucessfully made
                            if ($raw_data_resp && $dec_data_resp){
                                // Request response
                                echo "Data received". PHP_EOL;
                                http_response_code(200);
                            }else{
                                // Request response
                                echo "Request received". PHP_EOL;
                                http_response_code(201);
                            }
                        }else{
                            // Request response
                            echo "Bad request". PHP_EOL;
                            http_response_code(400);
                        }
                    }else{
                        // Request response
                        echo "Bad request". PHP_EOL;
                        http_response_code(400);
                    }
                } else {
                    // Request response
                    echo "Bad request". PHP_EOL;
                    http_response_code(400);
                }
            }else{
                // Request response
                echo "Bad request". PHP_EOL;
                http_response_code(400);
            }
        } else {
            // Request response
            echo "Method not allowed". PHP_EOL;
            http_response_code(405);
        }
    }catch(Exception $exception){
        // Request response
        echo "Internal server error". PHP_EOL;
        http_response_code(500);
    }
