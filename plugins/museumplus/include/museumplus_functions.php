<?php
/**
* Execute a cURL request
* 
* @param string $url            URL
* @param string $basic_auth     Username and password used for basic authentication.
*                               MUST follow the "username:password" syntax to work!
* @param string $content_type   Content type header value (e.g application/xml)
* @param string $request_method HTTP request methods (e.g GET, POST, PUT, DELETE) 
* @param string $data           Posted data (e.g XML)
* 
* @return array Response information such status code (e.g 200), headers and actual body
*/
function do_http_request($url, $basic_auth, $content_type, $request_method, $data)
    {
    $curl_handle = curl_init();

    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_HEADER, false);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);

    if(strpos($basic_auth, ':') !== false)
        {
        list($username, $password) = explode(':', $basic_auth);

        curl_setopt($curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl_handle, CURLOPT_USERPWD, "{$username}:{$password}");
        }

    // Set HTTP headers
    $request_http_headers = array();

    if(trim($content_type) != '')
        {
        $request_http_headers[] = "Content-Type: {$content_type}";
        }

    if(!empty($request_http_headers))
        {
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $request_http_headers);
        }
    // End of setting HTTP headers

    // Set request method and posted data
    curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, $request_method);
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);

    // Get a list of response headers in order to have the ability to react to them (e.g can be useful to avoid reaching 
    // rate limits or knowing content type)
    $curl_response_headers = array();
    curl_setopt(
        $curl_handle,
        CURLOPT_HEADERFUNCTION,
        function($curl, $header) use (&$curl_response_headers)
            {
            $length = strlen($header);
            $header = explode(':', $header, 2);

            // Invalid header
            if(count($header) < 2)
                {
                return $length;
                }

            $name = strtolower(trim($header[0]));

            if(!array_key_exists($name, $curl_response_headers))
                {
                $curl_response_headers[$name] = array(trim($header[1]));
                }
            else
                {
                $curl_response_headers[$name][] = trim($header[1]);
                }

            return $length;
            }
    );

    $result = curl_exec($curl_handle);
    $response_status_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    curl_close($curl_handle);

    $response = array(
        'status_code' => $response_status_code,
        'headers'     => $curl_response_headers,
        'result'      => $result,
    );

    return $response;
    }