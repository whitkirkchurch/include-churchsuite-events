<?php
/*
Plugin Name: Include ChurchSuite Events
Plugin URI: https://github.com/whitkirkchurch/include-churchsuite-events
Description: Gets a list of events from a ChurchSuite account, and includes it as part of a post or page.
Version: 1.2.2
Author: St Mary's Church, Whitkirk
Author URI: https://whitkirkchurch.org.uk
License:     GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

function limit($iterable, $limit)
{
    foreach ($iterable as $key => $value) {
        if (!$limit--) {
            break;
        }
        yield $key => $value;
    }
}

function cs_events_shortcode($atts = [])
{
    if (isset($atts['account'])) {
        $account = $atts['account'];
        $base_url =
            'https://' . $account . '.churchsuite.co.uk/embed/calendar/json';
        unset($atts['account']);
    } else {
        return 'Missing "account" parameter!';
    }

    if (isset($atts['link_titles'])) {
        $link_titles = (bool) $atts['link_titles'];
        unset($atts['link_titles']);
    } else {
        $link_titles = false;
    }

    if (isset($atts['show_years'])) {
        $show_years = $atts['show_years'];
        unset($atts['show_years']);
    } else {
        $show_years = false;
    }

    if (isset($atts['show_date'])) {
        $show_date = $atts['show_date'];
        unset($atts['show_date']);
    } else {
        $show_date = true;
    }

    if (isset($atts['show_end_times'])) {
        $show_end_times = (bool) $atts['show_end_times'];
        unset($atts['show_end_times']);
    } else {
        $show_end_times = false;
    }

    if (isset($atts['show_locations'])) {
        $show_locations = (bool) $atts['show_locations'];
        unset($atts['show_locations']);
    } else {
        $show_locations = false;
    }

    if (isset($atts['show_descriptions'])) {
        $show_descriptions = (bool) $atts['show_descriptions'];
        unset($atts['show_descriptions']);
    } else {
        $show_descriptions = true;
    }

    if (isset($atts['limit_to_count'])) {
        $limit_to_count = (int) $atts['limit_to_count'];
        unset($atts['limit_to_count']);
    } else {
        $limit_to_count = true;
    }

    try {
        $params = [];

        foreach ($atts as $attribute => $value) {
            $params[$attribute] = $value;
        }

        $params_string = http_build_query($params);
        $query_url = $base_url . '?' . $params_string;

        $json = file_get_contents($query_url);
        $data = json_decode($json);

        $last_date = null;

        date_default_timezone_set('Europe/London');

        if ($limit_to_count) {
            $data_to_loop = limit($data, $limit_to_count);
        } else {
            $data_to_loop = $data;
        }

        // This is where most of the magic happens
        foreach ($data_to_loop as $event) {
            // Build the event URL, we use this a couple of times
            $event_url =
                'https://' .
                $account .
                '.churchsuite.co.uk/events/' .
                $event->identifier;

            // Build the object for the JSON-LD representation
            $json_ld = [
                '@context' => 'http://www.schema.org',
                '@type' => 'Event',
                'name' => $event->name,
                'url' => $event_url,
                'description' => $event->description,
                'startDate' => date(
                    DATE_ISO8601,
                    strtotime($event->datetime_start)
                ),
                'endDate' => date(
                    DATE_ISO8601,
                    strtotime($event->datetime_end)
                ),
            ];

            // Tack on the image, if we have one
            if (isset($event->images->lg)) {
                $json_ld['image'] = $event->images->lg->url;
            }

            // Set attendance mode
            if ($event->location->type == 'online') {
                $json_ld['eventAttendanceMode'] =
                    'https://schema.org/OnlineEventAttendanceMode';
                $json_ld['location'] = [
                    '@type' => 'VirtualLocation',
                    'url' => $event->location->url,
                ];
            } else {
                $json_ld['eventAttendanceMode'] =
                    'https://schema.org/OfflineEventAttendanceMode';
                $json_ld['location'] = [
                    '@type' => 'Place',
                    'name' => $event->location->name,
                    'address' => [
                        '@type' => 'PostalAddress',
                        'postalCode' => $event->location->address,
                    ],
                ];
            }

            // Flag cancelled events
            if ($event->status == 'cancelled') {
                $json_ld['eventStatus'] = 'https://schema.org/EventCancelled';
            } else {
                $json_ld['eventStatus'] = 'https://schema.org/EventScheduled';
            }

            // And output it!
            $output .=
                '<script type="application/ld+json">' .
                json_encode($json_ld) .
                '</script>';

            // Turn the time into an actual object
            $start_time = strtotime($event->datetime_start);

            $date = date('Y-m-d', $start_time);

            // Make sure we only show the date once per day
            if ($date != $last_date && $show_date) {
                if ($last_date == null) {
                    $output .= '<div class="cs_events--dateblock">';
                } else {
                    $output .= '</div><div class="cs_events--dateblock">';
                }
                $last_date = $date;
                $output .=
                    '<h3 class="cs_events--date">' . date('l j F', $start_time);

                if (
                    $show_years and
                    ($show_years == 'always' or
                        $show_years == 'different' and
                            date('Y', $start_time) != date('Y'))
                ) {
                    $output .= ' ' . date('Y', $start_time);
                }

                $output .= '</h3>';
            }

            $output .= '<div class="cs_events--event">';

            if (isset($event->images->thumb) && $event->description != '') {
                if ($link_titles == true) {
                    $output .=
                        '<a href="https://' .
                        $account .
                        '.churchsuite.co.uk/events/' .
                        $event->identifier .
                        '">';
                }
                $output .=
                    '<img class="cs_events--event--image hidden-xs hidden-sm" src="' .
                    $event->images->thumb->url .
                    '">';
                if ($link_titles == true) {
                    $output .= '</a>';
                }
            }

            // Output the event title
            $output .= '<h4 class="cs_events--event--title">';

            if ($event->status == 'cancelled') {
                $output .= '<span style="text-decoration:line-through">';
            } elseif ($event->status == 'pending') {
                $output .= '<span style="font-style: italic">';
            }

            if ($link_titles == true) {
                $output .= '<a href="' . $event_url . '">';
            }

            $output .=
                '<span class="cs_events--event--time">' .
                date('g.i a', $start_time);

            if ($show_end_times) {
                $output .=
                    ' &ndash; ' .
                    date('g.i a', strtotime($event->datetime_end));
            }

            $output .=
                '</span><span class="cs_events--event--name">' .
                $event->name .
                '</span>';

            if ($link_titles == true) {
                $output .= '</a>';
            }

            if ($event->status == 'cancelled') {
                $output .= '</span>';
            } elseif ($event->status == 'pending') {
                $output .= '?</span>';
            }

            $output .= '</h4>';

            if ($event->status == 'cancelled') {
                $output .=
                    '<p><strong>This event has been cancelled.</strong></p>';
            }

            if ($show_locations && $event->location->name) {
                $output .=
                    '<p><i>' .
                    htmlspecialchars_decode($event->location->name) .
                    '</i></p>';
            }

            if ($show_descriptions and $event->description != '') {
                $output .= htmlspecialchars_decode($event->description);
            }

            $output .= '<div style="clear:both"></div></div>';
        }

        $output .= '</div>';

        return $output;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

add_shortcode('churchsuite_events', 'cs_events_shortcode');
