<?php
/*
Plugin Name: Include ChurchSuite Events
Plugin URI: https://github.com/whitkirkchurch/include-churchsuite-events
Description: Gets a list of events from a ChurchSuite site, and includes it as part of a post or page.
Version: 1.0
Author: St Mary's Church, Whitkirk
Author URI: https://whitkirkchurch.org.uk
License:     GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/


function cs_events_shortcode($atts = [])
{

    if (isset($atts['site'])){
        $site_id = $atts['site'];
        $base_url = 'https://' . $site_id . '.churchsuite.co.uk/embed/calendar/json';
        unset($atts['site']);
    } else {
        return 'Missing "site" parameter!';
    }

    if (isset($atts['link_titles'])){
        $link_titles = (bool) $atts['link_titles'];
        unset($atts['link_titles']);
    } else {
        $link_titles = false;
    }

    if (isset($atts['show_years'])){
        $show_years = $atts['show_years'];
        unset($atts['show_years']);
    } else {
        $show_years = false;
    }

    if (isset($atts['show_end_times'])){
        $show_end_times = (bool) $atts['show_end_times'];
        unset($atts['show_end_times']);
    } else {
        $show_end_times = false;
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

        $output = '<div class="cs_events--dateblock">';
        $last_date = null;

        // This is where most of the magic happens
        foreach ($data as $event) {

            // Build the event URL, we use this a couple of times
            $event_url = 'https://' . $site_id . '.churchsuite.co.uk/events/' . $event->identifier;

            // Build the object for the JSON-LD representation
            $json_ld = [
                '@context' => 'http://www.schema.org',
                '@type' => 'Event',
                'name' => $event->name,
                'url' => $event_url,
                'description' => $event->description,
                'location' => [
                    '@type' => 'Place',
                    'name' => $event->location->name,
                    'address' => [
                        '@type' => 'PostalAddress',
                        'postalCode' => $event->location->address
                    ]
                ],
                'startDate' => $event->datetime_start,
                'endDate' => $event->datetime_end
            ];

            // Tack on the image, if we have one
            if (isset($event->images->lg)){
                $json_ld['image'] = $event->images->lg->url;
            }

            // And output it!
            $output .= '<script type="application/ld+json">' . json_encode($json_ld) . '</script>';

            // Turn the time into an actual object
            $start_time = strtotime($event->datetime_start);

            $date = date('Y-m-d', $start_time);

            // Make sure we only show the date once per day
            if ($date != $last_date){
                $last_date = $date;
                $output .= '</div><div class="cs_events--dateblock">';
                $output .= '<h3 class="cs_events--date">' . date('l j<\s\up>S</\s\up> F', $start_time);

                if ($show_years and ($show_years == 'always' or ($show_years == 'different' and date('Y', $start_time) != date('Y')))) {
                    $output .= ' ' . date('Y', $start_time);
                }

                $output .= '</h3>';
            }

            $output .= '<div class="cs_events--event">';

            if (isset($event->images->thumb) && $event->description != ''){
                if ($link_titles == true){
                    $output .= '<a href="https://' . $site_id . '.churchsuite.co.uk/events/' . $event->identifier . '">';
                }
                $output .= '<img class="cs_events--event--image hidden-xs hidden-sm" src="'. $event->images->thumb->url . '">';
                if ($link_titles == true){
                    $output .= '</a>';
                }
            }

            $output .= '<h4 class="cs_events--event--title">';

            if ($link_titles == true){
                $output .= '<a href="' . $event_url . '">';
            }

            $output .= '<span class="cs_events--event--time">' . date('g:ia', $start_time);

            if ($show_end_times) {
                $output .= '&mdash;' . date('g:ia', strtotime($event->datetime_end));
            }

            $output .='</span><span class="cs_events--event--name">' . $event->name . '</span>';

            if ($link_titles == true){
                $output .= '</a>';
            }

            $output .='</h4>';

            if ($event->description != '') {
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
