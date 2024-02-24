<?php

require_once 'classes/Database.php';
require_once 'classes/StatParser.php';
require_once 'classes/Html.php';

if (php_sapi_name() === 'cli') {
    if ($argc < 2) {
        echo "Usage: {$argv[0]} <path_to_db>\n";
        die;
    }

    try {
        $db = new Database($argv[1]);
        $stat = new StatParser();

        foreach ($stat->parseHistory() as $entry) {
            $db->writeHistory(
                $entry['code'],
                $entry['artist'],
                $entry['title'],
                $entry['time']
            );
        }

        foreach ($stat->parseListeners() as $entry) {
            $db->writeListeners(
                $entry['name'],
                $entry['code'],
                $entry['listeners']
            );
        }
    } catch (Exception $e) {
        // TODO: better logging
        echo $e->getMessage();
        die;
    }
}