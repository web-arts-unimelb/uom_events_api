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
   * The format the API outputs data in.
   *
   * Options are: JSON, JSONP, XML.
   */
  const EVENTS_API_FORMAT = 'json';

  /**
   * The key to access the API.
   */
  private $api_key;

  /**
   * Function to fetch the data.
   */
  private $fetcher = 'file_get_contents';

  /**
   * Fetcher function options.
   */
  private $fetcher_options = FALSE;

  /**
   * Function to parse the data.
   */
  private $parser = 'json_decode';

  /**
   * Parser function options.
   */
  private $parser_options = FALSE;

  /**
   * Events display mode.
   */
  private $full;

  /**
   * Optional JSONP callback.
   */
  private $callback;

  /**
   * Optional filter key.
   */
  private $filter;

  /**
   * Filter values to be passed to the filter specified by the $filter key.
   */
  private $filters;

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
   * Fetch past events counting from today
   */
  function pastEventsByTag($tag, $num_of_years, $full = FALSE) {
  	$curr_month = date('n');
  	$curr_year = date('Y');
  	$curr_date = date('j');
 
  	$return_data = array();  	
  	for($i=0; $i<$num_of_years; $i++) {
		
			$filter_year = $curr_year - $i;	
			if($i == 0) {
				$month_counter = $curr_month;
			} else {
				$month_counter = 12;
			}
  		
			for($k=0; $k<$month_counter; $k++) {
  			$filter_month = $month_counter - $k;
  			$options = array('month'=>$filter_month, 'year'=>$filter_year);
  
  			// Set filter to use month
  			$this->setFilter('month', $options); 
  			$month_events = $this->fetchData('events/all/tagged/'. rawurlencode($tag), $full);
  			
  			$filtered_month_events = array();
  			if($curr_year == $filter_year && $curr_month == $filter_month) {
  				foreach($month_events as $single_event) {
  					if( strtotime($single_event->start_time) <= time() ) {
  						$filtered_month_events[] = $single_event;
  					}
  				}
  			}
  			else {
  				$filtered_month_events = $month_events;
  			}
  			
  			if(count($filtered_month_events) > 0) {
  				foreach($filtered_month_events as $filtered_month_event) {
  					$return_data[] = $filtered_month_event;
  				}
  			}	
  		}
  	}
  	
  	return $return_data;
  }

	function pastMonthEventsByTag($tag, $max_event_num=5, $full=FALSE) {
		$return_data = array();
		$filter_month = $curr_month = date('n');
    $filter_year = $curr_year = date('Y');
		$event_num_looking_for = $max_event_num;	

		// Go back for 1 year only
		for($i=0; $i<=1; $i++) {
			$filter_year = $filter_year - $i;

			if($filter_year == $curr_year) {
				while($filter_month >= 1) { 
					$options = array('month'=>$filter_month, 'year'=>$filter_year);
					$this->setFilter('month', $options);	
					$month_events = $this->fetchData('events/all/tagged/'. rawurlencode($tag), $full);

					// Sort the events
          usort($month_events, "_my_event_compare_desc");

          // Remove any current/upcoming events
          $month_events = $this->_filter_past_events($month_events);

					// Count
					$month_events_num = count($month_events);

					if($month_events_num >= $event_num_looking_for) {
						for($k=0; $k<$event_num_looking_for; $k++) {
							$month_event = array_shift($month_events);
							array_push($return_data, $month_event);
						}

						$event_num_looking_for = 0;
					} 
					elseif($month_events_num > 0 && $month_events_num < $event_num_looking_for) {
						for($x=0; $x<$month_events_num; $x++) {
							$month_event = array_shift($month_events);
              array_push($return_data, $month_event);	
						}

						$event_num_looking_for = $event_num_looking_for - $month_events_num;
						$filter_month = $filter_month - 1;
					}
					else {
						$filter_month = $filter_month - 1;
						continue;
					}

					// Completely return 
          if(count($return_data) >= $max_event_num) {
						// Sort the events
          	usort($return_data, "_my_event_compare_desc");

						return $return_data;
          }
				}		
			} else {
				$filter_month = 12;
				while($filter_month >= 1) {
					$options = array('month'=>$filter_month, 'year'=>$filter_year);
					$this->setFilter('month', $options);
          $month_events = $this->fetchData('events/all/tagged/'. rawurlencode($tag), $full);
          $month_events_num = count($month_events);
	
					// Sort the events
          usort($month_events, "_my_event_compare_desc");

          if($month_events_num >= $event_num_looking_for) {
            for($k=0; $k<$event_num_looking_for; $k++) {
              $month_event = array_shift($month_events);
              array_push($return_data, $month_event);
            }

            $event_num_looking_for = 0;
          }
          elseif($month_events_num > 0 && $month_events_num < $event_num_looking_for) {
            usort($month_events, "_my_event_compare_desc");

            for($x=0; $x<$month_events_num; $x++) {
              $month_event = array_shift($month_events);
              array_push($return_data, $month_event);
            }

            $event_num_looking_for = $event_num_looking_for - $month_events_num;
						$filter_month = $filter_month - 1;
          }
          else {
						$filter_month = $filter_month - 1;
            continue;
          }

          // Completely return 
          if(count($return_data) >= $max_event_num) {
            return $return_data;
          }
				}
			}
		}
		// End loop

		// If it exhausts the loop, but still not enough data, return whatever in $return_data
		return $return_data; 
	}

	function _filter_past_events($month_events) {
		$return_data = array();

		$month_events = array_filter($month_events, function($obj){
    	if(isset($obj->start_time)) {
    		$event_start_time = strtotime($obj->start_time);
      	$current_time = time();
      	if($event_start_time < $current_time)
      		return true;
      	else
      		return false;
    	}
    	else {
    	  return false;
    	}
   	});

    foreach($month_events as $event) {
			if(!is_null($event))
				$return_data[] = $event;	
    }

		return $return_data;
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
  function currentEventsByType($type, $full = FALSE) {
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
  function currentEventsByHost($host, $full = FALSE) {
    return $this->fetchData('events/current/hosted_by/' . rawurlencode($host), $full);
  }

  /**
   * Fetch an event by id.
   *
   * @param $id
   *   An event id.
   * @param $full
   *   Boolean flag that determines whether to return minimum
   *   event info or a full set of data.
   *
   * @return
   *   An event object.
   */
  function getEvent($id, $full = FALSE) {
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
   * Set the function used to fetch the raw data.
   *
   * The fetcher defaults to file_get_contents().
   */
  function setFetcher($fetcher, $options = array()) {
    $this->fetcher = $fetcher;
    $this->fetcher_options = $options;
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
   * Turn the response into JSONP with the specified callback.
   */
  function setCallback($callback) {
    $this->callback = $callback;
  }

  /**
   * Set filters that should be passed to the API backend.
   *
   * @todo: These filters are not validated, so it's possible to overwrite
   * the `display', `api_key' and other required parameters currently.
   */
  function setFilter($filter, $values) {
    $this->filter  = $filter;
    $this->filters = $values;
  }

  /**
   * Set a flag to retrieve full event info.
   */
  function displayFull() {
    $this->full = 'true';
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
    $response = call_user_func_array($this->fetcher, array($url, $this->fetcher_options));
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
    if (!empty($this->full)) {
      $params['full'] = $this->full;
    }

    // Add the callback if set.
    if (!empty($this->callback)) {
      $params['callback'] = $this->callback;
    }

    // Add any filter values if a filter key is set.
    if (!empty($this->filter)) {
      $params['filter'] = $this->filter;
      foreach ($this->filters as $key => $value) {
        $params[$key] = $value;
      }
    }

    $query = http_build_query($params);
    $return_url = 'http://' . EventsAPI::EVENTS_HOSTNAME . '/'. EventsAPI::EVENTS_API . '/' . $url . '.'. EventsAPI::EVENTS_API_FORMAT . '?' . $query;

		//test
		//dsm($return_url);

    return $return_url;
  }

} // End Class
