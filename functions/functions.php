<?php 

require 'config/config.php';

/**
 * Call API and get result
 * $method: POST, PUT, GET etc 
 * $url: api url
 * $data: array("param" => "value") ==> index.php?param=value
 */
function CallAPI($method, $url, $data = false)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    // curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    // curl_setopt($curl, CURLOPT_USERPWD, "username:password");

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}


/**
 * Get address, city, state, region, country, countrycode from ip
 * $ip: client ip
 * $purpose: locatin to get
 * $deep_detect: deeply detect?
 */
function ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
    $output = NULL;
    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
        $ip = $_SERVER["REMOTE_ADDR"];
        if ($deep_detect) {
            if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
    }
    $purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
    $support    = array("country", "countrycode", "state", "region", "city", "location", "address");
    $continents = array(
        "AF" => "Africa",
        "AN" => "Antarctica",
        "AS" => "Asia",
        "EU" => "Europe",
        "OC" => "Australia (Oceania)",
        "NA" => "North America",
        "SA" => "South America"
    );
    if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
        $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
        if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
            switch ($purpose) {
                case "location":
                    $output = array(
                        "city"           => @$ipdat->geoplugin_city,
                        "state"          => @$ipdat->geoplugin_regionName,
                        "country"        => @$ipdat->geoplugin_countryName,
                        "country_code"   => @$ipdat->geoplugin_countryCode,
                        "continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                        "continent_code" => @$ipdat->geoplugin_continentCode
                    );
                    break;
                case "address":
                    $address = array($ipdat->geoplugin_countryName);
                    if (@strlen($ipdat->geoplugin_regionName) >= 1)
                        $address[] = $ipdat->geoplugin_regionName;
                    if (@strlen($ipdat->geoplugin_city) >= 1)
                        $address[] = $ipdat->geoplugin_city;
                    $output = implode(", ", array_reverse($address));
                    break;
                case "city":
                    $output = @$ipdat->geoplugin_city;
                    break;
                case "state":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "region":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "country":
                    $output = @$ipdat->geoplugin_countryName;
                    break;
                case "countrycode":
                    $output = @$ipdat->geoplugin_countryCode;
                    break;
            }
        }
    }
    return $output;
}

/**
 * GET marketcheck api result
 * $url: api url
 */
function get_api_Result($url) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => API_URL . $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array("host: marketcheck-prod.apigee.net")
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return null;
    } else {
        return $response;
    }
}

/**
 * GET Search Auto-complete with Field & Input
 * $field: The name of the field in which to search the given input
 * $input: The input to be searched for auto completion.
 */
function search_auto_complete_with_field_and_input($field, $input) {
    
    // Search Auto-complete with Field & Input
    $search_auto_complete_with_field_and_input = "search/auto-complete?";

    $url = $search_auto_complete_with_field_and_input;

    $url .= "api_key=" . API_KEY . "&";
    // $url .= "country=" . COUNTRY_CODE . "&";
    $url .= "field=" . $field . "&";
    $url .= "input=" . $input;
    
    //Add country
    // $url .= "&country=CA";

    return get_api_Result($url);
    // return $url;
}

/**
 * GET Search Auto-complete with selections
 * $field: The name of the field in which to search the given input
 * $input: The input to be searched for auto completion.
 */
function search_auto_complete_with_selection($selection_field, $field, $input, $vehicle) {
    
    // Search Auto-complete with Field & Input
    $url  = "search/auto-complete?";

    $url .= "api_key=" . API_KEY . "&";
    // $url .= "country=" . COUNTRY_CODE . "&";
    $url .= "field=" . $field . "&";
    $url .= "input=" . $input . "&";

    if($selection_field == "ymmt") {
        //set year, model, make, trim
        $url .= "year="  . $vehicle['year']  . "&";
        $url .= "make="  . $vehicle['make']  . "&";
        $url .= "model=" . $vehicle['model'] . "&";
        $url .= "trim="  . $vehicle['trim'];
        if(array_key_exists('drive_train', $vehicle))
            $url .= "&drivetrain=". $vehicle['drive_train'];
        // $url .= "term_counts=true&";
        // $url .= "state=" . $state;
    }
    else if($selection_field == "ymm") {
        $url .= "year="  . $vehicle['year']  . "&";
        $url .= "make="  . $vehicle['make']  . "&";
        $url .= "model=" . $vehicle['model'];
    }
    else if($selection_field == "ym") {
        $url .= "year="  . $vehicle['year']  . "&";
        $url .= "make="  . $vehicle['make'];
    }
    return get_api_Result($url);
    // return $url;
}

function year_make_model($ymm_string) {
    
    if($ymm_string == '') 
        return null;

    $val_arr = explode(" ", $ymm_string);
    if(count($val_arr) == 1)
        return null;

    //get year
    $year  = $val_arr[0];
    $make  = '';
    $model = '';
    $result = '';
    // for($i = 1; $i < count($val_arr); $i++)
    for($i = 1; $i < 2; $i++)
    {
        $vehicle = array();
        if($i > 1)
            $make .= " ";
        $make .= $val_arr[$i];
        $vehicle = [
            'year' => $year, 
            'make' => $make
        ];
        $result = search_auto_complete_with_selection('ym', 'model', '', $vehicle);
        $terms = json_decode($result, true)["terms"];
        $model = count($terms);
        if(count($terms) > 0) {
            $model_array = array_slice($val_arr, $i+1);
            $model = join(" ", $model_array);
            break;
        }
    }
    return array(
        'year' => $year,
        'make' => $make,
        'model' => $model
    );
}
