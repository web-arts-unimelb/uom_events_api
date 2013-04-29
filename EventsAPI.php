<?php
/**
 * @file EventAPI.php
 * Class that interfaces with the University of Melbourne events website.
 */

/**
 * Class definition.
 */
class EventsAPI {

  /**
   * The hostname where the API is hosted.
   */
  const EVENTS_HOSTNAME = 'events.unimelb.edu.au';

  /**
   * The API endpoint base URL.
   *
   * This happens to also include the API version number.
   */
  const EVENTS_API = 'api/v1';

  /**
   * The key to access the API.
   */
  private $api_key;

  /**
   * Callback function to fetch the data.
   */
  private $callback = 'file_get_contents';

  /**
   * Callback function options.
   */
  private $callback_options = FALSE;

  /**
   * Callback function to parse the data.
   */
  private $parser = 'json_decode';

  /**
   * Parser function options.
   */
  private $parser_options = FALSE;

  /**
   * Events display mode.
   */
  private $display;

  /**
   * Constructor.
   *
   * @param $key
   *   An API key as provided at http://events.unimelb.edu.au/admin
   */
  function __construct($key) {
    $this->api_key = $key;
  }

  /**
   * Events!
   */

  /**
   * Fetch all upcoming events.
   *
   * @param $full
   *   Boolean flag that determines whether to return minimum
   *   event info or a full set of data.
   *
   * @return
   *   An array of event objects.
   */
  function currentEvents($full = FALSE) {
    return $this->fetchData('events/current', $full);
  }

  /**
   * Fetch all upcoming events with a specific tag.
   *
   * @param $full
   *   Boolean flag that determines whether to return minimum
   *   event info or a full set of data.
   *
   * @return
   *   An array of event objects.
   */
  function currentEventsByTag($tag, $full = FALSE) {
    return $this->fetchData('events/current/tagged/' . rawurlencode($tag), $full);
  }

  /**
   * Fetch all upcoming events of a specific type.
   *
   * @param $full
   *   Boolean flag that determines whether to return minimum
   *   event info or a full set of data.
   *
   * @return
   *   An array of event objects.
   */
  function currentEventsByType($type) {
    return $this->fetchData('events/current/type/' . rawurlencode($type), $full);
  }

  /**
   * Fetch all upcoming events hosted by a specific department.
   *
   * @param $full
   *   Boolean flag that determines whether to return minimum
   *   event info or a full set of data.
   *
   * @return
   *   An array of event objects.
   */
  function currentEventsByHost($host) {
    return $this->fetchData('events/current/hosted_by/' . rawurlencode($host), $full);
  }

  /**
   * Fetch an event by id.
   *
   * @param $full
   *   Boolean flag that determines whether to return minimum
   *   event info or a full set of data.
   *
   * @return
   *   An event object.
   */
  function getEvent($id) {
    return $this->fetchData('events/' . rawurlencode($id), $full);
  }

  /**
   * Presenters!
   */

  /**
   * Fetch all presenters.
   *
   * @return
   *   An array of presenter objects.
   */
  function allPresenters() {
    return $this->fetchData('presenters');
  }

  /**
   * Fetch a presenter by id.
   *
   * @return
   *   A presenter object.
   */
  function getPresenter($id) {
    return $this->fetchData('presenters/' . rawurlencode($id));
  }

  /**
   * Recordings!
   */

  /**
   * Fetch all recordings.
   *
   * @return
   *   An array of recording objects.
   */
  function allRecordings() {
    return $this->fetchData('recordings');
  }

  /**
   * Fetch a recording by id.
   *
   * @return
   *   A recording object.
   */
  function getRecording($id) {
    return $this->fetchData('recordings/' . rawurlencode($id));
  }

  /**
   * Tags!
   */

  /**
   * Fetch all event tags.
   *
   * @return
   *   An array of tag objects.
   */
  function allTags() {
    return $this->fetchData('tags');
  }

  /**
   * Fetch all tags in upcoming events.
   *
   * @return
   *   An array of tag objects.
   */
  function currentTags() {
    return $this->fetchData('tags/current');
  }

  /**
   * Types!
   */

  /**
   * Fetch all event types.
   *
   * @return
   *   An array of type objects.
   */
  function allTypes() {
    return $this->fetchData('event_types');
  }

  /**
   * Fetch all upcoming event types.
   *
   * @return
   *   An array of type objects.
   */
  function currentTypes() {
    return $this->fetchData('event_types/current');
  }

  /**
   * Hosts!
   */

  /**
   * Fetch all hosts.
   *
   * @return
   *   An array of host objects.
   */
  function allHosts() {
    return $this->fetchData('hosts');
  }


  /**
   * Fetch all hosts of upcoming events.
   *
   * @return
   *   An array of host objects.
   */
  function currentHosts() {
    return $this->fetchData('hosts/current');
  }

  /**
   * It is possible to override the functions used to retrieve and parse the
   * data. This allows you to use functions internal to your app and possibly
   * add in error checking etc.
   */

  /**
   * Set the callback function used to fetch the raw data.
   *
   * The callback defaults to file_get_contents().
   */
  function setCallback($callback, $options = FALSE) {
    $this->callback = $callback;
    $this->callback_options = $options;
  }

  /**
   * Set the parser function used to parse the raw API data into PHP objects.
   *
   * The parser defaults to json_decode().
   */
  function setParser($parser, $options = NULL) {
    $this->parser = $parser;
    $this->parser_options = $options;
  }

  /**
   * Set a flag to retrieve full event info.
   */
  function displayFull() {
    $this->display = 'full';
  }

  /**
   * Below here are the internal methods used to actually retrieve and
   * parse data from the API endpoint.
   */

  /**
   * Wrapper that fetches data from an endpoint URL.
   *
   * @param $url
   *   A relative URL path.
   * @param $full
   *   Boolean flag that determines whether to return minimum
   *   event info or a full set of data.
   *
   * @return
   *
   * @return
   *   An array of objects or FALSE on error.
   *
   * This method is mostly copied from the Drupal 6 drupal_http_request()
   * function so that we can do a little bit of error checking.
   */
  private function fetchData($url, $full = FALSE) {
    // Set the flag if the user wants a full result.
    if ($full) {
      $this->displayFull();
    }

    // Construct a URL.
    $url = $this->buildEndpoint($url);

    // Fetch data from the constructed endpoint URL.
    $response = call_user_func_array($this->callback, array($url, $this->callback_options));
    if ($response === FALSE) {
      return FALSE;
    }

    // Parse out the data we want.
    $result = call_user_func_array($this->parser, array($response, $this->parser_options));
    if ($result === FALSE) {
      return FALSE;
    }

    return $result;
  }

  /**
   * Create an endpoint URL that can be used to fetch data.
   *
   * @param $url
   *   A relative URL path.
   *
   * @return
   *   A valid API endpoint URL.
   */
  private function buildEndpoint($url) {
    $params = array('auth_token' => $this->api_key);

    // Use the global display flag to determine if the user wants
    // a full event display.
    if (!empty($this->display)) {
      $params['display'] = $this->display;
    }

    $query = http_build_query($params);
    return 'http://' . EventsAPI::EVENTS_HOSTNAME . '/'. EventsAPI::EVENTS_API . '/' . $url . '?' . $query;
  }

} // End Class
