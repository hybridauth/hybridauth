<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2013, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Pinterest (By Eduardo Marcolino - https://github.com/eduardo-marcolino)
 */
class Hybrid_Providers_Pinterest extends Hybrid_Provider_Model_OAuth2 {
  public $scope = 'read_public,write_public,read_relationships,write_relationships';

  function initialize() {
    parent::initialize();
    $this->api->api_base_url = 'https://api.pinterest.com/v1/';
    $this->api->authorize_url = 'https://api.pinterest.com/oauth/';
    $this->api->token_url = 'https://api.pinterest.com/v1/oauth/token';
    $this->api->sign_token_name = 'access_token';
  }

  function loginBegin() {
    $parameters = array(
      'scope' => isset($this->config['scope']) ? $this->config['scope'] : $this->scope,
      'response_type' => 'token',
      'client_id' => $this->api->client_id,
      'redirect_uri' => $this->api->redirect_uri,
      'state' => isset($this->config['state']) ? $this->config['state'] : ''
    );
    if (is_array($parameters['scope'])) {
      $parameters['scope'] = implode(',', $parameters['scope']);
    }

    Hybrid_Auth::redirect($this->api->authorizeUrl($parameters));
  }

  function loginFinish() {
    if (isset($_REQUEST['access_token'])) {
      $token = $_REQUEST;
      $this->access_token = $token;

      // we should have an access_token unless something has gone wrong
      if (!isset($token["access_token"])) {
        throw new Exception("Authentication failed! {$this->providerId} returned an invalid access token.", 5);
      }

      $this->token('access_token', $token['access_token']);

      // set user as logged in to the current provider
      $this->setUserConnected();
      return;
    }
    parent::loginFinish();
  }

  function getUserProfile() {
    $profile = $this->api->api('me/', 'GET', array(
      'fields' => 'id,username,first_name,last_name,counts,image'
    ));

    if (!isset($profile->data->id)) {
      throw new Exception("User profile request failed! {$this->providerId} returned an invalid response:" . Hybrid_Logger::dumpData($profile), 6);
    }

    $data = $profile->data;

    $this->user->profile->identifier = $data->id;
    $this->user->profile->firstName = $data->first_name;
    $this->user->profile->lastName = $data->last_name;
    $this->user->profile->displayName = $data->username;

    if (isset($data->image->{'60x60'})) {
      $this->user->profile->photoURL = $data->image->{'60x60'}->url;
    }

    return $this->user->profile;
  }

  /**
   * Creates a board for the authenticated user.
   *
   * @param string $title
   *   Board title
   * @param string $description
   *   Board description
   *
   * @return mixed
   *   Success: array with fields ID, url and name
   *   Query error: array with fields message, error code
   *   Other error: Null
   */
  function createUserBoard($title, $description = NULL) {
    $args = array(
      'name' => $title
    );
    if (!empty($description)) {
      $args['description'] = $description;
    }
    $board = $this->api->api('boards/', 'POST', $args);
    $data = !empty($board->data) ? $board->data : NULL;

    return $data;
  }

  /**
   * Returns specified board for the authenticated user.
   *
   * @param string $username
   *   User's display name.
   * @param string $board_name
   *   Board machine name.
   *
   * @return mixed
   *   Success: array with fields ID, url and name.
   *   Query error: array with fields message, error code.
   *   Other error: Null.
   */
  function getUserBoard($username, $board_name) {
    $board = $this->api->api('boards/' . $username . '/' . $board_name . '/', 'GET');
    $data = !empty($board->data) ? $board->data : NULL;

    return $data;
  }

  /**
   * Returns a list of the authenticated user's public boards.
   *
   * @return mixed
   *   Success: list arrays with fields ID, url and name.
   *   Query error: array with fields message, error code.
   *   Other error: Null.
   */
  function getUserBoards() {
    $board = $this->api->api('me/boards/', 'GET');
    $data = !empty($board->data) ? $board->data : NULL;

    return $data;
  }

  /**
   * Returns a list of pins on the board.
   *
   * @todo Add parameter cursor
   *
   * @param string $username
   *   User's display name.
   * @param string $board_name
   *   Board machine name.
   *
   * @return mixed
   *   Success: list with arrays including pin fields ID, url, link and description.
   *   Query error: array with fields message, error code.
   *   Other error: Null.
   */
  function getUserBoardPins($username, $board_name) {
    $board = $this->api->api('boards/' . $username . '/' . $board_name . '/pins/', 'GET');
    $data = !empty($board->data) ? $board->data : NULL;

    return $data;
  }

  /**
   * @todo Changes the chosen board's name and/or description.
   */

  /**
   * Deletes the specified board.
   *
   * @param string $username
   *   User's display name.
   * @param string $board_name
   *   Board machine name.
   *
   * @return mixed
   *   Success: array with succes field.
   *   Query error: array with fields message, error code.
   *   Other error: Null.
   */
  function deleteUserBoard($username, $board_name) {
    $board = $this->api->api('boards/' . $username . '/' . $board_name . '/', 'DELETE');
    $data = !empty($board->data) ? $board->data : NULL;

    return $data;
  }

  /**
   * Creates a pin for the authenticated user.
   *
   * @param string $username
   *   User's display name.
   * @param string $board_name
   *   Board machine name.
   * @param string $note
   *   Pin's description.
   * @param string $img_type
   *   image: upload the image you want to pin using multipart form data.
   *   image_url: the link to the image that you want to pin.
   *   image_base64: the link of a base64 encoded image.
   * @param string $image
   *   Pin's image.
   * @param string $link
   *   URL the pin will link to when you click through.
   *
   * @return mixed
   *   Success: array with pin fields ID, note, url, link.
   *   Query error: array with fields message, error code.
   *   Other error: Null.
   */
  function createUserPin($username, $board_name, $note, $img_type, $image,  $link = NULL) {
    $data = array(
      'board' => $username . '/' . $board_name,
      'note' => $note,
    );
    if (!empty($link)) {
      $data['link'] = $link;
    }
    if (in_array($img_type, array('image', 'image_url', 'image_base64'))) {
      $data[$img_type] = $image;
    }
    $pin = $this->api->api('pins/', 'POST', $data);
    $data = !empty($pin->data) ? $pin->data : NULL;

    return $data;
  }
}
