<?php
    const VALIDATION_RULES = [
        'frame' => 'string'
    ];
    const FRAME_KEY = 'frame';

    // Function to validate and sanitize POST data
    function validateAndSanitize($postData) {
        $sanitizedData = [];
        
        // Check for each validation rule
        foreach (VALIDATION_RULES as $key => $rule) {
            if (isset($postData[$key])) {
                $value = $postData[$key];
                
                // Validate and sanitize depending validation rules
                switch ($rule) {
                    case 'string':
                        // Use htmlspecialchars to sanitize strings and prevent XSS
                        $sanitizedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                        break;
                    default:
                        // die("Error: Tipo de validación desconocido para '$key'.");
                        die();
                }
                
                // Store sanitized value
                $sanitizedData[$key] = $sanitizedValue;
            } else {
                //die("Error: El campo '$key' es requerido pero no está presente.");
                die();
            }
        }
        // Return sanitized POST data
        return $sanitizedData;
    }
