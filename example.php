<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/constant.php';
use Sunra\PhpSimple\HtmlDomParser;
ini_set('memory_limit', '-1');

define('APPLICATION_NAME', 'Gmail API PHP Quickstart');
define('CREDENTIALS_PATH', '~/.credentials/gmail-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/gmail-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Gmail::GMAIL_READONLY)
));

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

$argv = $_SERVER['argv'];
if(isset($argv) && count($argv)>1)
{
  foreach ($argv as $arg) {
    $e=explode("=",$arg);
    if(count($e)==2)
        $_GET[$e[0]]=$e[1];
    else    
        $_GET[$e[0]]=0;
  }
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfig(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, json_encode($accessToken));
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
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
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

function listMessages($service, $userId) {
  $pageToken = NULL;
  $messages = array();
  $opt_param = array();

  // if you want to filter with specific sender
  $opt_param['q'] = FROM_EMAIL; 

  do {
    try {
      if ($pageToken) {
        $opt_param['pageToken'] = $pageToken;
      }
      $messagesResponse = $service->users_messages->listUsersMessages($userId, $opt_param);
      
      if ($messagesResponse->getMessages()) {
        $messages = array_merge($messages, $messagesResponse->getMessages());
        $pageToken = $messagesResponse->getNextPageToken();
      }
    } catch (Exception $e) {
      print 'An error occurred: ' . $e->getMessage();
    }
  } while ($pageToken);

  return $messages;
}

function getMessage($service, $userId, $messageId) 
{   
  $optParamsGet = [];
  $optParamsGet['format'] = 'full'; // Display message in payload
  
  try {
    $message = $service->users_messages->get($userId, $messageId, $optParamsGet);
    return $message;
  } catch (Exception $e) {
    print 'An error occurred: ' . $e->getMessage();
  }
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Gmail($client);

$messageList = listMessages($service, USER);
$inboxMessage = [];

foreach($messageList as $mlist)
{
    $single_message = getMessage($service, USER, $mlist->id);

    $message_id = $mlist->id;
    $headers = $single_message->getPayload()->getHeaders();
    $snippet = $single_message->getSnippet();
    $parts = $single_message->getPayload()->getParts();

    if(isset($parts[0]['parts'][0]))
    {
      $body = $parts[0]['parts'][0]['body'];
      $rawData = $body->data;
      $sanitizedData = strtr($rawData,'-_', '+/');
      $decodedMessage = base64_decode($sanitizedData);
    }
    else if(isset($parts[0]['body']))
    {
      $body = $parts[0]['body'];
      $rawData = $body->data;
      $sanitizedData = strtr($rawData,'-_', '+/');
      $decodedMessage = base64_decode($sanitizedData);
    }

    foreach($headers as $single) {

        if ($single->getName() == 'Subject') {

            $message_subject = $single->getValue();

        }

        else if ($single->getName() == 'Date') {

            $message_date = $single->getValue();
            $message_date = date('M jS Y h:i A', strtotime($message_date));
        }

        else if ($single->getName() == 'From') {

            $message_sender = $single->getValue();
            $message_sender = str_replace('"', '', $message_sender);
        }
    }

    $state = true;
    if(isset($_GET['date_start']) && isset($_GET['date_end']))
    {
      $state = false;
      $date = date("Y-m-d H:i:s", strtotime($message_date));
      $date_start = date("Y-m-d 00:00:00", strtotime($_GET['date_start']));
      $date_end = date("Y-m-d 23:59:59", strtotime($_GET['date_end']));
      if($date >= $date_start && $date <= $date_end)
      {
        $state = true;
      }
    }

    // if you want to scrapping email content with some class or attributes. 
    // $dom = HtmlDomParser::str_get_html($decodedMessage);
    // $elems = $dom->find("div.someclass"); 

    $inboxMessage[] = [
        'messageId' => $message_id,
        'messageSnippet' => $snippet,
        'messageSubject' => $message_subject,
        'messageDate' => $message_date,
        'messageSender' => $message_sender
    ];
}

?>
<html>
  <head>
    <title>Retrieve email from gmail</title>
  </head>
  <body>
    <h3>Periode : <?php echo (isset($_GET["date_start"]) && isset($_GET["date_end"])) ? $_GET["date_start"]." s/d ".$_GET["date_end"] : "Semua"; ?></h3>
    <h3>Total Inbox : <?php echo count($inboxMessage); ?> Trip</h3>
    <table border="1">
      <?php
      if(count($inboxMessage) > 0)
      {
        foreach($inboxMessage as $row)
        {
          echo "<tr>";
          echo "<td>Message Date : {$row['messageDate']}<br />";
          echo "Message Id : {$row['messageId']}<br />";
          echo "Message Sender : {$row['messageSnippet']}<br />";
          echo "Message Snippet : {$row['messageSnippet']}<br />";
          echo "Message Subject : {$row['messageSubject']}</td>";
          echo "</tr>";
        }
      }
      ?>
    </table>
  </body>
</html>
