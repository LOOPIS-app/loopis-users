<?php
/**
 * Function to read city from postcode, scans csv to build map
 * 
 * @package LOOPIS_Users
 * @subpackage pn_map
 */

function loopis_get_city(string $post_code){
    $csv_path =  __DIR__ . '/../assets/sweden-zipcode.csv';
    
    $post_code = loopis_normalize_postcode($post_code);

    if($post_code===null){
        return null;
    }

    if(($csv_file=fopen($csv_path,'r'))===false){
        throw new RuntimeException('Cannot open csv :' . $csv_path);
    }

    while(($row = fgetcsv($csv_file)) !== false){
        if(!isset($row[0],$row[1])) {
            continue;
        }

        if ($row[0]===$post_code){
            fclose($csv_file);
            return ucfirst(strtolower(trim($row[1])));
        }
    }

    fclose($csv_file);
    return null;
}


function loopis_normalize_postcode(string $post_code){
    $post_code =  preg_replace('/\s+/', '', trim($post_code));
    return preg_match('/^\d{5}$/', $post_code) ? $post_code : null;
}

