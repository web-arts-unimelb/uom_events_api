University of Melbourne Events API
==================================

Provides a PHP wrapper for the events.unimelb.edu.au API.

Usage:
```php
  $key = '... my api key ...';
  $api = new EventsAPI($key);
  $events = $api->currentEvents();

  foreach ($events as $event) {
    print $event->title;
  }
```

Available public methods:

 * currentEvents($full = FALSE)
 * currentEventsByTag($tag, $full = FALSE)
 * currentEventsByType($type, $full = FALSE)
 * currentEventsByHost($host, $full = FALSE)
 * getEvent($id)
 * allPresenters()
 * getPresenter($id)
 * allRecordings()
 * getRecording($id)
 * allTags()
 * currentTags()
 * allTypes()
 * currentTypes()
 * allHosts()
 * currentHosts()

You can set the event methods to return a full set of info via a global flag:

 * displayFull()

It is possible to override the functions used to retrieve and parse the data.
This allows you to use functions internal to your app and possibly do some
error checking etc.

 * setFetcher($fetcher, $options = FALSE)
 * setParser($parser, $options = NULL)

You can specify a callback name and turn the response into JSONP:

  * setCallback($callback)

See http://events.unimelb.edu.au/admin for API keys and more information.
