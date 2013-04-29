University of Melbourne Events API
==================================

Provides a PHP wrapper for the events.unimelb.edu.au API.

Usage:

  $key = '... my api key ...';
  $api = new EventsAPI($key);
  $events = $api->currentEvents();

  foreach ($events as $event) {
    print $event->title;
  }

Available public methods:

 * currentEvents()
 * currentEventsByTag($tag)
 * currentEventsByType($type)
 * currentEventsByHost($host)
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

It is possible to override the functions used to retrieve and parse the
data. This allows you to use functions internal to your app and possibly
do some error checking etc.

 * setCallback($callback, $options = FALSE)
 * setParser($parser, $options = NULL)

See http://events.unimelb.edu.au/admin for API keys and more information.
