#### features

Features are whatever we put on to of a regular site.

A regular site would be defined as:

`Latest WordPress stable, single site installation running over http`.

The main file (stuff.php) defines a few hooks that allow us to build features on top of the site launching flow.

Some features are:

- Enabling of SSL.  (`ssl.php`).
- Addition of plugins.  (`plugins.php`).
- Setting of wp-config constants like WP_DEBU and WP_DEBUG_LOG.  (`wp-debug-log.php`).
- Launching multisite installations instead of single site( '')

Refer to the [API](docs/development.md#API) section of the development doc to find out about existing hooks.
