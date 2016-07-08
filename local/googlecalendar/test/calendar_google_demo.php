<?php

require_once realpath(dirname(__FILE__) . '/../../../lib/google/src/Google/autoload.php');
define('APPLICATION_NAME', 'Google Calendar API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__ . '/../certs/calendar-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/../certs/client_secret.json');

// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
    Google_Service_Calendar::CALENDAR)
));

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
    $client = new Google_Client();
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
    $client->setAuthConfigFile(CLIENT_SECRET_PATH);
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
    if (file_exists($credentialsPath)) {
        $accessToken = file_get_contents($credentialsPath);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->authenticate($authCode);

        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, $accessToken);
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->refreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, $client->getAccessToken());
    }
    return $client;
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

/*
 * API da agenda
 * https://developers.google.com/google-apps/calendar/v3/reference/
 */

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Calendar($client);
$option = -1;

while ($option != 0) {

    echo "Select the action?\n";
    echo "0 - Exit\n";

    echo "1 - List calendars\n";
    echo "2 - Create new calendar\n";
    echo "3 - Remove calendar\n";

    echo "4 - Listar eventos da agenda para os próximos 30 dias\n";
    echo "5 - Adicionar evento a agenda\n";
    echo "6 - Apagar um evento da agenda distante até 30 dias\n";


    $option = readline();

    if (is_numeric($option)) {

        switch ($option) {
            //Lista agendas
            case 1:
                $calendarList = $service->calendarList->listCalendarList();
                foreach ($calendarList->getItems() as $calendarListEntry) {
                    echo $calendarListEntry->getSummary() . " || " . $calendarListEntry->getId() . "\n";
                }
                break;

            case 2:
                // criar agenda 
                $calendar = new Google_Service_Calendar_Calendar();
                $date = date('H:i');
                $calendar->setSummary('Moodle Calendar');
                $calendar->setTimeZone('America/Los_Angeles');
                $createdCalendar = $service->calendars->insert($calendar);
                $calendarId = $createdCalendar->getId();
                
                printf("=====================================================================================================================\n");
                printf("Calendar: %s\n", print_r($createdCalendar, true));
                printf("=====================================================================================================================\n");
                printf("CalendarID %s\n", $calendarId);
                printf("=====================================================================================================================\n");

                /* Compartilha a agenda */
                $rule = new Google_Service_Calendar_AclRule();
                $scope = new Google_Service_Calendar_AclRuleScope();

                $scope->setType("user");
                $scope->setValue("ahwelp@univates.br");
                $rule->setScope($scope);
                $rule->setRole("reader");
                $createdRule = $service->acl->insert($calendarId, $rule);
                //echo $createdRule->getId();

                break;

            case 3:
                // apagar agenda 
                $calendar_list_id = [];
                $inner_option = -1;
                $calendarList = $service->calendarList->listCalendarList();
                foreach ($calendarList->getItems() as $foreach_key => $calendarListEntry) {
                    echo $foreach_key . ": " . $calendarListEntry->getSummary() . "\n";
                    $calendar_list_id[$foreach_key] = $calendarListEntry->getId();
                }
                echo "Qual o número da agenda a ser apagada?\n";
                $inner_option = readline();

                if (is_numeric($inner_option)) {
                    $service->calendars->delete($calendar_list_id[$inner_option]);
                }
                break;

            case 4:
                // Listar eventos               

                $calendar_id = '';
                $calendar_list_id = [];
                $inner_option = -1;

                $calendarList = $service->calendarList->listCalendarList();
                foreach ($calendarList->getItems() as $foreach_key => $calendarListEntry) {
                    echo $foreach_key . ": " . $calendarListEntry->getSummary() . "\n";
                    $calendar_list_id[$foreach_key] = $calendarListEntry->getId();
                }

                echo "Em qual agenda deverá ser listada?";
                $inner_option = readline();
                $calendar_id = $calendar_list_id[$inner_option];

                $params = array(
                    'calendarId' => $calendar_id
                        //'timeMax'   => '2016-07-02T19:00:00-03:00'
                );

                $events = $service->events->listEvents($params);

                var_dump($events);

                break;

            case 5:
                // Adicionar evento a agenda
                $calendar_id = '';
                $calendar_list_id = [];
                $inner_option = -1;

                $calendarList = $service->calendarList->listCalendarList();
                foreach ($calendarList->getItems() as $foreach_key => $calendarListEntry) {
                    echo $foreach_key . ": " . $calendarListEntry->getSummary() . "\n";
                    $calendar_list_id[$foreach_key] = $calendarListEntry->getId();
                }

                echo "Em qual agenda o evento será adicionado?";
                $inner_option = readline();
                $calendar_id = $calendar_list_id[$inner_option];

                echo "Qual o nome do evento?";
                $summary = readline();
                if (is_numeric($inner_option)) {

                    $event_data = array(
                        'summary' => $summary,
                        'description' => 'Criado no momento: ' . time(),
                        'start' => array(
                            'dateTime' => '2016-06-02T19:00:00-03:00',
                            'timeZone' => 'America/Sao_Paulo',
                        ),
                        'end' => array(
                            'dateTime' => '2016-06-02T20:00:00-03:00',
                            'timeZone' => 'America/Sao_Paulo',
                        ),
                    );

                    $event = new Google_Service_Calendar_Event($event_data);
                    $event = $service->events->insert($calendar_id, $event);

                    echo "Event created: " . $event->htmlLink . "\n";
                }
                break;
            case 6:
                break;
            default:
                break;
        }
    }
}   


