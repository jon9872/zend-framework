<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * PHP sample code for the YouTube data API.  Utilizes the Zend Framework 
 * Zend_Gdata component to communicate with the YouTube data API.
 *
 * Requires the Zend Framework Zend_Gdata component and PHP >= 5.1.4
 * This sample is run from within a web browser.  These files are required:
 * authentication_details.php - a script to view log output and session variables
 * operations.php - the main logic, which interfaces with the YouTube API
 * index.php - the HTML to represent the web UI, contains some PHP
 * video_app.css - the CSS to define the interface style
 * video_app.js - the JavaScript used to provide the video list AJAX interface
 *
 * NOTE: If using in production, some additional precautions with regards
 * to filtering the input data should be used.  This code is designed only
 * for demonstration purposes.
 */
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_YouTube');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_App_Exception');

/*
 * The main controller logic.
 *
 * POST used for all authenticated requests
 * otherwise use GET for retrieve and supplementary values
 */
session_start();
setLogging('on');
generateUrlInformation();

if (!isset($_POST['operation'])) {
    // if a GET variable is set then process the token upgrade
    if (isset($_GET['token'])) {
        updateAuthSubToken($_GET['token']);
    } else {
        if (loggingEnabled()) {
            logMessage('reached operations.php without $_POST or $_GET variables set', 'error');
            header('Location: index.php');
        }
    }
}

$operation = $_POST['operation'];

switch ($operation) {

    case 'create_upload_form':
        createUploadForm($_POST['videoTitle'], 
                         $_POST['videoDescription'], 
                         $_POST['videoCategory'], 
                         $_POST['videoTags']);
        break;

    case 'edit_meta_data':
        editVideoData($_POST['newVideoTitle'], 
                      $_POST['newVideoDescription'], 
                      $_POST['newVideoCategory'], 
                      $_POST['newVideoTags'], 
                      $_POST['videoId']);
        break;

    case 'check_upload_status':
        checkUpload($_POST['videoId']);
        break;

    case 'delete_video':
        deleteVideo($_POST['videoId']);
        break;    

    case 'auth_sub_request':
        generateAuthSubRequestLink();
        break;

    case 'auth_sub_token_upgrade':
        updateAuthSubToken($_GET['token']);
        break;

    case 'clear_session_var':
        clearSessionVar($_POST['name']);
        break;

    case 'set_developer_key':
        setDeveloperKey($_POST['developerKey']);
        break;

    case 'retrieve_playlists':
        retrievePlaylists();
        break;

    case 'create_playlist':
        createPlaylist($_POST['playlistTitle'], $_POST['playlistDescription']);
        break;

    case 'delete_playlist':
        deletePlaylist($_POST['playlistTitle']);
        break;

    case 'update_playlist':
        updatePlaylist($_POST['newPlaylistTitle'], 
                       $_POST['newPlaylistDescription'], 
                       $_POST['oldPlaylistTitle']);
        break;

    case (strcmp(substr($operation, 0, 7), 'search_') == 0):
        // initialize search specific information
        $searchType = substr($operation, 7);
        searchVideos($searchType, $_POST['searchTerm'], $_POST['startIndex'], $_POST['maxResults']);
        break;

    case 'show_video':
        echoVideoPlayer($_POST['videoId']);
        break;

    default:
        unsupportedOperation($_POST);
        break;
}

/**
 * Perform a search on youtube
 *
 * @param string $searchType The type of search to perform. If 'owner' then attempt to authenticate.
 * @param string $searchTerm The term to search on.
 * @param string $startIndex Start retrieving search results from this index.
 * @param string $maxResults The number of results to retrieve.
 */
function searchVideos($searchType, $searchTerm, $startIndex, $maxResults) 
{
  // create an unauthenticated service object
    $youTubeService = new Zend_Gdata_YouTube();
    $query = $youTubeService->newVideoQuery();
    $query->setQuery($searchTerm);
    $query->setStartIndex($startIndex);
    $query->setMaxResults($maxResults);

    switch ($searchType) {
        case 'most_viewed':
            $query->setFeedType('most viewed');
            $query->setTime('this_week');
            $feed = $youTubeService->getVideoFeed($query);
            break;
        case 'most_recent':
            $query->setFeedType('most recent');
            $query->setTime('this_week');
            $feed = $youTubeService->getVideoFeed($query);
            break;
        case 'recently_featured':
            $query->setFeedType('recently featured');
            $feed = $youTubeService->getVideoFeed($query);
            break;
        case 'top_rated':
            $query->setFeedType('top rated');
            $query->setTime('this_week');
            $feed = $youTubeService->getVideoFeed($query);
            break;
        case 'username':
            $feed = $youTubeService->getUserUploads($searchTerm);
            break;
        case 'all':
            $feed = $youTubeService->getVideoFeed($query);
            break;
        case 'owner':
            $httpClient = getAuthSubHttpClient();
            $youTubeService = new Zend_Gdata_YouTube($httpClient);
            try {
                $feed = $youTubeService->getVideoFeed('http://gdata.youtube.com/feeds/users/default/uploads');
                if (loggingEnabled()) {
                    logMessage($httpClient->getLastRequest(), 'request');
                    logMessage($httpClient->getLastResponse()->getBody(), 'response');
                }
            } catch (Zend_Gdata_App_Exception $e) {
                print 'ERROR - Could not obtain users video feed: '. $e->getMessage() .'<br />';
                return;
            }
            echoVideoList($feed, true);
            return;
        
        default:
            echo 'ERROR - Unknown search type - \'' . $searchType . '\'';
            return;
    }

    if (loggingEnabled()) {
        $httpClient = $youTubeService->getHttpClient();
        logMessage($httpClient->getLastRequest(), 'request');
        logMessage($httpClient->getLastResponse()->getBody(), 'response');
    }
    echoVideoList($feed);
}

/**
 * Finds the URL for the flash representation of the specified video.
 *
 * @param Zend_Gdata_YouTube_VideoEntry $entry The video entry
 * @return (string|null) The URL or null, if the URL is not found
 */
function findFlashUrl($entry) 
{
    foreach ($entry->mediaGroup->content as $content) {
        if ($content->type === 'application/x-shockwave-flash') {
            return $content->url;
        }
    }
    return null;
}

/**
 * Check the upload status of a video
 *
 * @param string $videoId The video to check.
 */
function checkUpload($videoId) 
{
    $httpClient = getAuthSubHttpClient();
    $youTubeService = new Zend_Gdata_YouTube($httpClient);

    $feed = $youTubeService->getuserUploads('default');
    $message = 'No further status information available yet.';

    foreach($feed as $videoEntry) {
        if ($videoEntry->getVideoId() == $videoId) {
            // check if video is in draft status
            try {
                $control = $videoEntry->getControl();
            } catch (Zend_Gdata_App_Exception $e) {
                print 'ERROR - not able to retrieve control element '. $e->getMessage();
                return;
            }
      
            if ($control instanceof Zend_Gdata_App_Extension_Control) {
                if (($control->getDraft() != null) && ($control->getDraft()->getText() == 'yes')) {
                    $state = $videoEntry->getVideoState();
                    if ($state instanceof Zend_Gdata_YouTube_Extension_State) {
                        $message = 'Upload status: '. $state->getName() .' '.  $state->getText();
                    } else {
                        print $message;
                    }
                }
            }
        }
    }
    print $message;
}

/**
 * Store location of the demo application into session variables.
 *
 */
function generateUrlInformation() 
{
    if (!isset($_SESSION['operationsUrl']) || !isset($_SESSION['homeUrl'])) {
        $_SESSION['operationsUrl'] = 'http://'. $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        $path = explode('/', $_SERVER['PHP_SELF']);
        $path[count($path)-1] = 'index.php';
        $_SESSION['homeUrl'] = 'http://'. $_SERVER['HTTP_HOST'] . implode('/', $path);
    }
}

/**
 * Log a message to the session variable array.
 *
 * @param string $message The message to log.
 * @param string $messageType The type of message to log.
 */
function logMessage($message, $messageType) 
{
    if (!isset($_SESSION['log_maxLogEntries'])) {
        $_SESSION['log_maxLogEntries'] = 20;
    }
  
    if (!isset($_SESSION['log_currentCounter'])) {
        $_SESSION['log_currentCounter'] = 0;
    }
  
    $currentCounter = $_SESSION['log_currentCounter'];
    $currentCounter++;
  
    if ($currentCounter > $_SESSION['log_maxLogEntries']) {
        $_SESSION['log_currentCounter'] = 0;
    }

    $logLocation = 'log_entry_'. $currentCounter .'_'. $messageType;
    $_SESSION[$logLocation] = $message;
    $_SESSION['log_currentCounter'] = $currentCounter;
}

/**
 * Update an existing video's meta-data.
 *
 * @param string $newVideoTitle The new title for the video entry.
 * @param string $newVideoDescription The new description for the video entry.
 * @param string $newVideoCategory The new category for the video entry.
 * @param string $newVideoTags The new set of tags for the video entry (whitespace separated).
 * @param string $videoId The video id for the video to be edited.
 */
function editVideoData($newVideoTitle, $newVideoDescription, $newVideoCategory, $newVideoTags, $videoId) 
{
    $httpClient = getAuthSubHttpClient();
    $youTubeService = new Zend_Gdata_YouTube($httpClient);
    $feed = $youTubeService->getVideoFeed('http://gdata.youtube.com/feeds/users/default/uploads');
    $videoEntryToUpdate = null;

    foreach($feed as $entry) {
        if ($entry->getVideoId() == $videoId) {
            $videoEntryToUpdate = $entry;
            break;
        }
    }

    if (!$videoEntryToUpdate instanceof Zend_Gdata_YouTube_VideoEntry) {
        print 'ERROR - Could not find a video entry with id '. $videoId .'<br />'.
              printCacheWarning();
        return;
    }
    
    try {
        $putUrl = $videoEntryToUpdate->getEditLink()->getHref();
    } catch (Zend_Gdata_App_Exception $e) {
        print 'ERROR - Could not obtain video entry\'s edit link: '. $e->getMessage() .'<br />';
        return;
    }

    $mediaGroup = $youTubeService->newMediaGroup();
    $mediaGroup->title = $youTubeService->newMediaTitle()->setText($newVideoTitle);
    $mediaGroup->description = $youTubeService->newMediaDescription()->setText($newVideoDescription);
    $categorySchema = 'http://gdata.youtube.com/schemas/2007/categories.cat';
    $mediaGroup->category = array(
        $youTubeService->newMediaCategory()->setText($newVideoCategory)->setScheme($categorySchema)
    );
    
    // convert tags from space separated to comma separated
    $videoTagsArray = explode(' ', trim($newVideoTags));

    // strip out empty array elements
    foreach($videoTagsArray as $key => $value) {
        if (strlen($value) < 2) {
            unset($videoTagsArray[$key]);
        }
    }
  
    $mediaGroup->keywords = $youTubeService->newMediaKeywords()->setText(implode(', ', $videoTagsArray));
    $videoEntryToUpdate->mediaGroup = $mediaGroup;

    try {
        $updatedEntry = $youTubeService->updateEntry($videoEntryToUpdate, $putUrl);
        if (loggingEnabled()) {
            logMessage($httpClient->getLastRequest(), 'request');
            logMessage($httpClient->getLastResponse()->getBody(), 'response');
        }
    } catch (Zend_Gdata_App_HttpException $httpException) {
        print 'ERROR '. $httpException->getMessage()  .' HTTP details<br />'. 
            '<textarea cols="100" rows="20">'. 
            ($httpClient->getLastResponse() ? $httpClient->getLastResponse()->getBody() : 'No response from the server') .
            '</textarea><br />'. 
            '<a href="authentication_details.php">click here to view details of last request</a><br />';
            return;
    } catch (Zend_Gdata_App_Exception $e) {
        print 'ERROR - Could not post video meta-data: '. $e->getMessage();
        return;
    }
        print 'Entry updated successfully.<br /><a href="#" onclick="'.
              'ytVideoApp.presentFeed(\'search_owner\', 5, 0, \'none\'); ytVideoApp.refreshSearchResults();"'.
              '">(refresh your video listing)</a><br />'.
              printCacheWarning();
}

/**
 * Create upload form by sending the incoming video meta-data to youtube and retrieving a new entry.
 *
 * @param string $VideoTitle The title for the video entry.
 * @param string $VideoDescription The description for the video entry.
 * @param string $VideoCategory The category for the video entry.
 * @param string $VideoTags The set of tags for the video entry (whitespace separated).
 */
function createUploadForm($videoTitle, $videoDescription, $videoCategory, $videoTags) 
{
    $httpClient = getAuthSubHttpClient();
    $youTubeService = new Zend_Gdata_YouTube($httpClient);
    $newVideoEntry = new Zend_Gdata_YouTube_VideoEntry();

    $nextUrl = null;

    $mediaGroup = $youTubeService->newMediaGroup();
    $mediaGroup->title = $youTubeService->newMediaTitle()->setText($videoTitle);
    $mediaGroup->description = $youTubeService->newMediaDescription()->setText($videoDescription);

    //make sure first character in category is capitalized
    $videoCategory = strtoupper(substr($videoCategory, 0, 1)).substr($videoCategory, 1);
    $categorySchema = 'http://gdata.youtube.com/schemas/2007/categories.cat';
    $mediaGroup->category = array(
        $youTubeService->newMediaCategory()->setText($videoCategory)->setScheme($categorySchema)
    );

    // convert videoTags from whitespace separated into comma separated
    $videoTagsArray = explode(' ', trim($videoTags));
    $mediaGroup->keywords = $youTubeService->newMediaKeywords()->setText(implode(', ', $videoTagsArray));
    $newVideoEntry->mediaGroup = $mediaGroup;

    $tokenHandlerUrl = 'http://gdata.youtube.com/action/GetUploadToken';
    try {
        $tokenArray = $youTubeService->getFormUploadToken($newVideoEntry, $tokenHandlerUrl);
        if (loggingEnabled()) {
            logMessage($httpClient->getLastRequest(), 'request');
            logMessage($httpClient->getLastResponse()->getBody(), 'response');
        }
    } catch (Zend_Gdata_App_HttpException $httpException) {
        print 'ERROR - Could not retrieve token for syndicated upload. '. $httpException->getMessage() .' HTTP details<br />'. 
              '<textarea cols="100" rows="20">'. 
              ($httpClient->getLastResponse() ? $httpClient->getLastResponse()->getBody() : 'No response from the server') .
              '</textarea><br />'. 
              '<a href="authentication_details.php">click here to view details of last request</a><br />';
        return;
    } catch (Zend_Gdata_App_Exception $e) {
        print 'ERROR - Could not retrieve token for syndicated upload. '. 
              $e->getMessage() .
              '<br /><a href="authentication_details.php">click here to view details of last request</a><br />';
        return;
    }
  
    $tokenValue = $tokenArray['token'];
    $postUrl = $tokenArray['url'];

    // place to redirect user after upload
    if (!$nextUrl) {
        $nextUrl = $_SESSION['homeUrl'];
    }
  
    print <<< END
        <br /><form action="${postUrl}?nexturl=${nextUrl}"
        method="post" enctype="multipart/form-data">
        <input name="file" type="file"/>
        <input name="token" type="hidden" value="${tokenValue}"/>
        <input value="Upload Video File" type="submit" />
        </form>
END;
}

/**
 * Deletes a Video.
 *
 * @param string $videoId Id of the video to be deleted.
 */
function deleteVideo($videoId) 
{
    $httpClient = getAuthSubHttpClient();
    $youTubeService = new Zend_Gdata_YouTube($httpClient);
    $feed = $youTubeService->getVideoFeed('http://gdata.youtube.com/feeds/users/default/uploads');
    $videoEntryToDelete = null;

    foreach($feed as $entry) {
        if ($entry->getVideoId() == $videoId) {
            $videoEntryToDelete = $entry;
            break;
        }
    }

    // check if videoEntryToUpdate was found
    if (!$videoEntryToDelete instanceof Zend_Gdata_YouTube_VideoEntry) {
        print 'ERROR - Could not find a video entry with id '. $videoId .'<br />';
        return;
    }
    
    try {
        $httpResponse = $youTubeService->delete($videoEntryToDelete);
        if (loggingEnabled()) {
            logMessage($httpClient->getLastRequest(), 'request');
            logMessage($httpClient->getLastResponse()->getBody(), 'response');
        }

    } catch (Zend_Gdata_App_HttpException $httpException) {
        print 'ERROR '. $httpException->getMessage() . ' HTTP details<br />'. 
              '<textarea cols="100" rows="20">'. 
              ($httpClient->getLastResponse() ? $httpClient->getLastResponse()->getBody() : 'No response from the server') 
              .'</textarea><br />'. 
              '<a href="authentication_details.php">'. 
              'click here to view details of last request</a><br />';
        return;
    } catch (Zend_Gdata_App_Exception $e) {
        print 'ERROR - Could not delete video: '. $e->getMessage();
        return;
    }

    print 'Entry deleted succesfully.<br />'. $httpResponse->getBody() .'<br /><a href="#" onclick="' . 
          'ytVideoApp.presentFeed(\'search_owner\', 5, 0, \'none\');"'. 
          '">(refresh your video listing)</a><br />'.
          printCacheWarning();
}

/**
 * Enables logging.
 *
 * @param string $loggingOption 'on' to turn logging on, 'off' to turn logging off.
 * @param integer|null $maxLogItems Maximum items to log, default is 10.
 */
function setLogging($loggingOption, $maxLogItems = 10)
{
    switch ($loggingOption) {
        case 'on' : 
            $_SESSION['logging'] = 'on'; 
            $_SESSION['log_currentCounter'] = 0;
            $_SESSION['log_maxLogEntries'] = $maxLogItems;
            break;
    
        case 'off': 
            $_SESSION['logging'] = 'off'; 
            break;
    }
}

/**
 * Check whether logging is enabled.
 * 
 * @return boolean Return true if session variable for logging is set to 'on'.
 */
function loggingEnabled() 
{
    if ($_SESSION['logging'] == 'on') {
        return true;
    }
}

/**
 * Unset a specific session variable.
 *
 * @param string $name Name of the session variable to delete.
 */
function clearSessionVar($name) 
{
    if (isset($_SESSION[$name])) {
        unset($_SESSION[$name]);
    }
    header('Location: authentication_details.php');
}

/**
 * Generate an AuthSub request Link.
 *
 * @param string $nextUrl URL to redirect to after performing the authentication.
 */
function generateAuthSubRequestLink($nextUrl = null)
{
    $scope = 'http://gdata.youtube.com';
    $secure = false;
    $session = true;
  
    if (!$nextUrl) {
        generateUrlInformation();
        $nextUrl = $_SESSION['operationsUrl'];
    }
    
    $url = Zend_Gdata_AuthSub::getAuthSubTokenUri($nextUrl, $scope, $secure, $session);
    echo '<a href="'. $url .'">Click here to authenticate with YouTube</a>';
}   

/**
 * Store the developer key provided in the session variables.
 *
 * @param string $developerKey A valid YouTube developer key.
 */
function setDeveloperKey($developerKey) 
{
    $_SESSION['developerKey'] = $developerKey;

    if (!isset($_SESSION['sessionToken'])) {
        echo generateAuthSubRequestLink() .' (Developer Key set.)<br />'; 
    } else {
        print 'Developer Key has been set.<br /><a href="">(reload the page)</a>';
    }
}

/**
 * Upgrade the single-use token to a session token.
 *
 * @param string $singleUseToken A valid single use token that is upgradable to a session token.
 */
function updateAuthSubToken($singleUseToken) 
{
    try {
        $sessionToken = Zend_Gdata_AuthSub::getAuthSubSessionToken($singleUseToken);
    } catch (Zend_Gdata_App_Exception $e) {
        print 'ERROR - Token upgrade for '. $singleUseToken .' failed : '. $e->getMessage();
        return;
    }
  
    $_SESSION['sessionToken'] = $sessionToken;
    generateUrlInformation();
    header('Location: '. $_SESSION['homeUrl']);
}

/**
 * Convenience method to obtain an authenticted Zend_Http_Client object.
 *
 * @return Zend_Http_Client An authenticated client.
 */
function getAuthSubHttpClient()
{
    try {
        $httpClient = Zend_Gdata_AuthSub::getHttpClient($_SESSION['sessionToken']);
    } catch (Zend_Gdata_App_Exception $e) {
        print 'ERROR - Could not obtain authenticated Http client object. '. 
              $e->getMessage();
        return;
    }
    $httpClient->setHeaders('X-GData-Key', 'key='. $_SESSION['developerKey']);
    return $httpClient;
}

/**
 * Echo img tags for the first thumbnail representing each video in the 
 * specified video feed. Upon clicking the thumbnails, the video should
 * be presented.
 *
 * @param Zend_Gdata_YouTube_VideoFeed $feed The video feed
 */
function echoThumbnails($feed) 
{
    foreach ($feed as $entry) {
        $videoId = $entry->getVideoId();
        $firstThumbnail = $entry->mediaGroup->thumbnail[0]->url;
        echo '<img id="'. $videoId .'" class="thumbnail" src="' . $firstThumbnail .'" width="130" ' .
             'height="97" onclick="ytVideoApp.presentVideo(\'' . $videoId . '\', 1);" title="click to watch: '. $entry->mediaGroup->title .'" >';
     }
}

/**
 * Echo the list of videos in the specified feed.
 *
 * @param Zend_Gdata_YouTube_VideoFeed $feed The video feed.
 * @param boolean|null $authenticated If true then the videoList will attempt to create additional forms to edit video meta-data.
 */
function echoVideoList($feed, $authenticated = false) 
{
    $table = '<table id="videoResultList" class="videoList"><tbody>';
    $results = 0;
     
    foreach ($feed as $entry) {
        $thumbnailUrl = null;
        $videoId = $entry->getVideoId();
        if (count($entry->mediaGroup->thumbnail) > 0) {
          $thumbnailUrl = $entry->mediaGroup->thumbnail[0]->url;
        }
        if (!$thumbnailUrl) {
            $thumbnailUrl = 'notfound.jpg';
        }
    
        $videoTitle = $entry->mediaGroup->title;
        $videoDescription = $entry->mediaGroup->description;
        $videoCategoryArray = $entry->mediaGroup->category;
        $videoTags = $entry->mediaGroup->keywords;

        $table .= '<tr id="'. $videoId .'">'.
              '<td width="130"><img onclick="ytVideoApp.presentVideo(\''. $videoId. '\')" src="'. $thumbnailUrl. '" /></td>'. 
              '<td>'. 
              '<a href="#" onclick="ytVideoApp.presentVideo(\''. $videoId. '\')">'. $videoTitle .'</a>'. 
              '<p class="videoDescription">'. $videoDescription .'</p>'. 
              '<p class="videoCategory">category: '. implode(' ', $videoCategoryArray) .'</p>'. 
              '<p class="videoTags">tagged: '. $videoTags .'</p>';

          if ($authenticated) {
              $table .= '<p class="edit"><a onclick="ytVideoApp.presentMetaDataEditForm(\''. 
                  $videoTitle .'\', \''. 
                  $videoDescription .'\', \''. 
                  $videoCategoryArray . '\', \''. 
                  $videoTags .'\', \'' . 
                  $videoId .'\');" href="#">edit video data</a> | <a href="#" onclick="ytVideoApp.confirmDeletion(\''. 
                  $videoId .'\');">delete this video</a></p><br clear="all">';
          }

    $table .= '</td></tr>';
    $results++;
    }
  
    if ($results < 1) {
        echo '<br />No results found<br /><br />';
    } else {
        echo $table .'</tbody></table><br />';
    }
}

/**
 * Echo the video embed code, related videos and videos owned by the same user
 * as the specified videoId.
 *
 * @param string $videoId The video
 */
function echoVideoPlayer($videoId) {
    $youTubeService = new Zend_Gdata_YouTube();
    
    try {
        $entry = $youTubeService->getVideoEntry($videoId);
    } catch (Zend_Gdata_App_HttpException $e) {
        print 'ERROR - Entry is not valid or still being processed. '. $e->getResponse()->getBody();
        return;
    }

    $videoTitle = $entry->mediaGroup->title;
    $videoUrl = findFlashUrl($entry);
    $relatedVideoFeed = getRelatedVideos($entry->getVideoId());
    $topRatedFeed = getTopRatedVideosByUser($entry->author[0]->name);
    
    print <<<END
        <b>$videoTitle</b><br />
        <object width="425" height="350">
        <param name="movie" value="${videoUrl}&autoplay=1"></param>
        <param name="wmode" value="transparent"></param>
        <embed src="${videoUrl}&autoplay=1" type="application/x-shockwave-flash" wmode="transparent"
        width=425" height="350"></embed>
        </object>
END;

    echo '<br />';
    echoVideoMetadata($entry);
    echo '<br /><b>Related:</b><br />';
    echoThumbnails($relatedVideoFeed); 
    echo '<br /><b>Top rated videos by user:</b><br />';
    echoThumbnails($topRatedFeed); 
}

/**
 * Returns a feed of videos related to the specified video
 *
 * @param string $videoId The video
 * @return Zend_Gdata_YouTube_VideoFeed The feed of related videos
 */
function getRelatedVideos($videoId) 
{
    $youTubeService = new Zend_Gdata_YouTube();
    $ytQuery = $youTubeService->newVideoQuery();
    // show videos related to the specified video
    $ytQuery->setFeedType('related', $videoId);
    // order videos by rating
    $ytQuery->setOrderBy('rating');
    // retrieve a maximum of 5 videos
    $ytQuery->setMaxResults(5);
    // retrieve only embeddable videos
    $ytQuery->setFormat(5);
    return $youTubeService->getVideoFeed($ytQuery);
}

/**
 * Returns a feed of top rated videos for the specified user
 *
 * @param string $user The username 
 * @return Zend_Gdata_YouTube_VideoFeed The feed of top rated videos
 */
function getTopRatedVideosByUser($user) 
{
    $userVideosUrl = 'http://gdata.youtube.com/feeds/users/' . 
                   $user . '/uploads';
    $youTubeService = new Zend_Gdata_YouTube();
    $ytQuery = $youTubeService->newVideoQuery($userVideosUrl);  
    // order by the rating of the videos
    $ytQuery->setOrderBy('rating');
    // retrieve a maximum of 5 videos
    $ytQuery->setMaxResults(5);
    // retrieve only embeddable videos
    $ytQuery->setFormat(5);
    return $youTubeService->getVideoFeed($ytQuery);
}

/**
 * Echo video metadata
 * 
 * @param Zend_Gdata_YouTube_VideoEntry $entry The video entry
 */
function echoVideoMetadata($entry) 
{
    $title = $entry->mediaGroup->title;
    $description = $entry->mediaGroup->description;
    $authorUsername = $entry->author[0]->name;
    $authorUrl = 'http://www.youtube.com/profile?user=' . 
                 $authorUsername;
    $tags = $entry->mediaGroup->keywords;
    $duration = $entry->mediaGroup->duration->seconds;
    $watchPage = $entry->mediaGroup->player[0]->url;
    $viewCount = $entry->statistics->viewCount;
    $rating = $entry->rating->average;
    $numRaters = $entry->rating->numRaters;
    $flashUrl = findFlashUrl($entry);
    print <<<END
        <b>Title:</b> ${title}<br />
        <b>Description:</b> ${description}<br />
        <b>Author:</b> <a href="${authorUrl}">${authorUsername}</a><br />
        <b>Tags:</b> ${tags}<br />
        <b>Duration:</b> ${duration} seconds<br />
        <b>View count:</b> ${viewCount}<br />
        <b>Rating:</b> ${rating} (${numRaters} ratings)<br />
        <b>Flash:</b> <a href="${flashUrl}">${flashUrl}</a><br />
        <b>Watch page:</b> <a href="${watchPage}">${watchPage}</a> <br />
END;
}

/**
 * Print message about YouTube caching.
 * 
 * @return string A message
 */
function printCacheWarning() 
{
    return '<p class="note">Please note that the change may not be reflected in the API'.
        ' immediately due to caching.<br/>Please refer to the API documentation for more details.</p>';
}

/**
 * Retrieve playlists for the currently authenticated user.
 */
function retrievePlaylists() {
  
    $httpClient = getAuthSubHttpClient();
    $youTubeService = new Zend_Gdata_YouTube($httpClient);
    $feed = $youTubeService->getPlaylistListFeed('default'); 

    if (loggingEnabled()) {
        logMessage($httpClient->getLastRequest(), 'request');
        logMessage($httpClient->getLastResponse()->getBody(), 'response');
    }
  
    if (!$feed instanceof Zend_Gdata_YouTube_PlaylistListFeed) {
        print 'ERROR - Could not retrieve playlists<br />'.
        printCacheWarning();
        return;
    }
  
    $playlistEntries = '<ul>';
    $entriesFound = 0;
    foreach($feed as $entry) {
        $playlistTitle = $entry->getTitleValue();
        $playlistDescription = $entry->getDescription()->getText();
        $playlistEntries .=  '<li><h3>'. $playlistTitle . 
            '</h3>'. $playlistDescription .' | '.
            '<a href="#" onclick="ytVideoApp.prepareUpdatePlaylistForm(\'' . 
            $playlistTitle . '\', \''. $playlistDescription .'\'); ">update</a> | '.
            '<a href="#" onclick="ytVideoApp.confirmPlaylistDeletion(\''. 
            $playlistTitle .'\');">delete</a></li>';
        $entriesFound++;
    }        
  
    $playlistEntries .= '</ul><br /><a href="#" onclick="ytVideoApp.prepareCreatePlaylistForm(); return false;">'.
                        'Add new playlist</a><br /><div id="addNewPlaylist"></div>';

    if (loggingEnabled()) {
        logMessage($httpClient->getLastRequest(), 'request');
        logMessage($httpClient->getLastResponse()->getBody(), 'response');
    }
    if ($entriesFound > 0) {
        print $playlistEntries;
    } else {
        print 'No playlists found';
    }
}

/**
 * Create a new playlist for the currently authenticated user
 *
 * @param string $playlistTitle Title of the new playlist 
 * @param string $playlistDescription Description for the new playlist
 */
function createPlaylist($playlistTitle, $playlistDescription) 
{
    $httpClient = getAuthSubHttpClient();
    $youTubeService = new Zend_Gdata_YouTube($httpClient);
    $feed = $youTubeService->getPlaylistListFeed('default'); 
    if (loggingEnabled()) {
        logMessage($httpClient->getLastRequest(), 'request');
        logMessage($httpClient->getLastResponse()->getBody(), 'response');
    }
  
    $newPlaylist = $youTubeService->newPlaylistListEntry();
    $newPlaylist->description = $youTubeService->newDescription()->setText($playlistDescription);
    $newPlaylist->title = $youTubeService->newTitle()->setText($playlistDescription);
  
    if (!$feed instanceof Zend_Gdata_YouTube_PlaylistListFeed) {
        print 'ERROR - Could not retrieve playlists<br />'.
        printCacheWarning();
        return;
    }

    $playlistFeedUrl = 'http://gdata.youtube.com/feeds/users/default/playlists';
  
    try {
        $updatedEntry = $youTubeService->insertEntry($newPlaylist, $playlistFeedUrl);
        if (loggingEnabled()) {
            logMessage($httpClient->getLastRequest(), 'request');
            logMessage($httpClient->getLastResponse()->getBody(), 'response');
        }
    } catch (Zend_Gdata_App_HttpException $httpException) {
        print 'ERROR '. $httpException->getMessage() . ' HTTP details<br />'. 
              '<textarea cols="100" rows="20">'. 
              ($httpClient->getLastResponse() ? $httpClient->getLastResponse()->getBody() : 'No response from the server') .
              '</textarea><br />'. 
              '<a href="authentication_details.php">click here to view details of last request</a><br />';
        return;
    } catch (Zend_Gdata_App_Exception $e) {
        print 'ERROR - Could not create new playlist: '. $e->getMessage();
        return;
    }

    print 'Playlist added succesfully.<br /><a href="#" onclick="' . 
          'ytVideoApp.retrievePlaylists();"'. 
          '">(refresh your playlist listing)</a><br />'.
          printCacheWarning();
}

/**
 * Delete a playlist
 *
 * @param string $playlistTitle Title of the playlist to be deleted
 */
function deletePlaylist($playlistTitle)
{
    $httpClient = getAuthSubHttpClient();
    $youTubeService = new Zend_Gdata_YouTube($httpClient);
    $feed = $youTubeService->getPlaylistListFeed('default'); 
    if (loggingEnabled()) {
        logMessage($httpClient->getLastRequest(), 'request');
        logMessage($httpClient->getLastResponse()->getBody(), 'response');
    }
    
    $playlistEntryToDelete = null;
    
    foreach($feed as $playlistEntry) {
        if ($playlistEntry->getTitleValue() == $playlistTitle) {
            $playlistEntryToDelete = $playlistEntry;
            break;
        }
    }

    if (!$playlistEntryToDelete instanceof Zend_Gdata_YouTube_PlaylistListEntry) {
        print 'ERROR - Could not retrieve playlist to be deleted<br />'.
              printCacheWarning();
              return;
    }

    try {
        $response = $playlistEntryToDelete->delete();
        if (loggingEnabled()) {
            logMessage($httpClient->getLastRequest(), 'request');
            logMessage($httpClient->getLastResponse()->getBody(), 'response');
        }
    } catch (Zend_Gdata_App_HttpException $httpException) {
        print 'ERROR '. $httpException->getMessage() . ' HTTP details<br />'. 
              '<textarea cols="100" rows="20">'. 
              ($httpClient->getLastResponse() ? $httpClient->getLastResponse()->getBody() : 'No response from the server') .
              '</textarea><br />'. 
              '<a href="authentication_details.php">click here to view details of last request</a><br />';
        return;
    } catch (Zend_Gdata_App_Exception $e) {
        print 'ERROR - Could not delete the playlist: '. $e->getMessage();
        return;
    } 

    print 'Playlist deleted succesfully.<br /><a href="#" onclick="ytVideoApp.retrievePlaylists();">'. 
          '(refresh your playlist listing)</a><br />' .printCacheWarning();
}

/**
 * Delete a playlist
 *
 * @param string $newplaylistTitle New title for the playlist to be updated
 * @param string $newPlaylistDescription New description for the playlist to be updated
 * @param string $oldPlaylistTitle Title of the playlist to be updated
 */
function updatePlaylist($newPlaylistTitle, $newPlaylistDescription, $oldPlaylistTitle) 
{
    $httpClient = getAuthSubHttpClient();
    $youTubeService = new Zend_Gdata_YouTube($httpClient);
    $feed = $youTubeService->getPlaylistListFeed('default'); 

    if (loggingEnabled()) {
        logMessage($httpClient->getLastRequest(), 'request');
        logMessage($httpClient->getLastResponse()->getBody(), 'response');
    }
  
    $playlistEntryToDelete = null;
  
    foreach($feed as $playlistEntry) {
        if ($playlistEntry->getTitleValue() == $oldplaylistTitle) {
            $playlistEntryToDelete = $playlistEntry;
            break;
        }
    }

    if (!$playlistEntryToDelete instanceof Zend_Gdata_YouTube_PlaylistListEntry) {
        print 'ERROR - Could not retrieve playlist to be updated<br />'.
        printCacheWarning();
        return;
    }
  
    try {
        $response = $playlistEntryToDelete->delete();
        if (loggingEnabled()) {
            logMessage($httpClient->getLastRequest(), 'request');
            logMessage($httpClient->getLastResponse()->getBody(), 'response');
        }
    } catch (Zend_Gdata_App_HttpException $httpException) {
        print 'ERROR '. $httpException->getMessage() . ' HTTP details<br />'. 
              '<textarea cols="100" rows="20">'. 
              ($httpClient->getLastResponse() ? $httpClient->getLastResponse()->getBody() : 'No response from the server') .
              '</textarea><br />'. 
              '<a href="authentication_details.php">click here to view details of last request</a><br />';
        return;
    } catch (Zend_Gdata_App_Exception $e) {
        print 'ERROR - Could not delete the playlist: '. $e->getMessage();
        return;
    }

    print 'Playlist deleted succesfully.<br /><a href="#" onclick="' . 
          'ytVideoApp.retrievePlaylists();"'. 
          '">(refresh your playlist listing)</a><br />'.
          printCacheWarning();
}

/**
 * Helper function if an unsupported operation is passed into this files main loop.
 *
 * @param array $post (Optional) The post variables that accompanied the operation, if available.
 */
function unsupportedOperation($_POST) 
{
    $message = 'ERROR An unsupported operation has been called - post variables received '.
                      print_r($_POST, true);
                  
    if (loggingEnabled()) {
        logMessage($message, 'error');
    }
    print $message;
}

?>
