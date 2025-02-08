=== Include ChurchSuite Events ===
Contributors: jacksonj04
Tags: churchsuite, events
Requires at least: 4.7
Tested up to: 6.0.1
Stable tag: v1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://whitkirkchurch.org.uk/donate

Gets a list of events from a ChurchSuite account, and includes it as part of a post or page.

== Description ==

Gets a list of events from a ChurchSuite account, and includes it as part of a post or page. Passing of various parameters control date ranges, categories, linking of titles and so-on.

Also embeds a JSON-LD representation of the event, which search engines like Google can use to [do interesting things](https://developers.google.com/search/docs/data-types/event).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress

== Usage ==

To include a list of events, add the `[churchsuite_events]` shortcode to a page.

You _must_ include the `account` parameter, giving your ChurchSuite account ID. For example, for the ChurchSuite account "canterbury.churchsuite.co.uk" you would use:

```[churchsuite_events account="canterbury"]```

You can also use any parameters listed under the *Calendar JSON feed* section of the [ChurchSuite API embed documentation](https://github.com/ChurchSuite/churchsuite-api/blob/master/modules/embed.md#calendar-json-feed). For example:

```[churchsuite_events account="canterbury" category="2" featured="1"]```

will only include events from category '2', which are featured.

There are some additional parameters you can pass:

* `link_titles`: Turn the title of each event in the list into a link to the ChurchSuite event page. Defaults to false.
* `show_date`: Display the dates of events. Defaults to true.
* `show_years`: If set to `always`, will always show the year in each date. If set to `different`, will only show years in dates where they are not the current year. Defaults to false.
* `show_end_times`: Display the time an event is scheduled to end. Defaults to false.
* `show_locations`: Display details of an event's location. Defaults to false.
* `show_descriptions`: Display an event's description if present. Defaults to true.
* `show_images`: Display an event's image if present. Defaults to true.
* `exclude_categories`: A comma-delimited list of category IDs to exclude from the output. Defaults to an empty array.

== Changelog ==

= 1.0 =

* Added a shortcode to embed a list of events from ChurchSuite into a page or post.

= 1.1 =

* Adds `show_date` option
* Adds `show_location` option
* Adds `show_description` option
* Fixes some timezone uncertainty
* Fixes dates not being correctly presented as ISO 8601
* Adds image to JSON-LD representation where known.
* Adds support for `eventAttendanceMode` and `eventStatus` parameters in JSON-LD.
* Updates date and time format to match preferred style

= 1.2 =

* Rename `site` to `account` to be more consistent with how ChurchSuite refers to accounts
* Add support for styling pending events

= 1.2.1 =

* Add `limit_to_count` to restrict the number of results
* Fixed bug where an empty date block would appear at the top of the list

= 1.2.2 =

* Updated "missing site parameter" error message, to "missing account parameter"

= 1.3.0 =

* Added `exclude_categories` parameter.

= Unreleased =

* Added `display_images` parameter.
