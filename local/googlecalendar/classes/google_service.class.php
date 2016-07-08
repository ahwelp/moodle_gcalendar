<?php

/**
 * Google Calendar Integration - 
 *
 * @package    local_googlecalendar
 * @author     Artur Welp <ahwelp@univates.br>
 * @author     Maurício Severo da Silva <mss@univates.br>
 * @author     Alexandre Sturmer Wolf <awolf@univates.br>
 * @copyright  2016 Univates - htttp://www.univates.br/
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once realpath(dirname(__FILE__) . '/../../../lib/google/src/Google/autoload.php');
define('APPLICATION_NAME', 'Moodle integration with Google calendar');
define('CREDENTIALS_PATH', __DIR__ . '/../certs/calendar-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/../certs/client_secret.json');

// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
Google_Service_Calendar::CALENDAR)
));



class GoogleService {

    private $service;
    private $client;

    function __construct() {
        $this->load_service();
    }

    function get_service() {
        return $this->service;
    }

    function set_service($service) {
        $this->service = $service;
    }

    function get_client() {
        return $this->client;
    }

    function set_client($client) {
        $this->client = $client;
    }

    function load_client() {
        $client = new Google_Client();
        $client->setApplicationName(APPLICATION_NAME);
        $client->setScopes(SCOPES);
        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        $client->setAccessType('offline');

        // Load previously authorized credentials from a file.
        $credentialsPath = $this->expandHomeDirectory(CREDENTIALS_PATH);
        if (file_exists($credentialsPath)) {
            $accessToken = file_get_contents($credentialsPath);
        } else {
            
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, $client->getAccessToken());
        }
        $this->client = $client;
        return $client;
    }

    function load_service() {
        $this->service = new Google_Service_Calendar($this->load_client());
    }

    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */
    function expandHomeDirectory($path) {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }

}
