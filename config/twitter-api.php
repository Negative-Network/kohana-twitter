<?php  defined('SYSPATH') OR die('No direct script access.');
/*
 * See https://dev.twitter.com to create you app 
 * See https://twitter.com/settings/applications to revoke access key and renegociate
 */

return array(
	'default' => 'user',
	'api' => array(
		'user' => array(
			'api-uri' => 'statuses/user_timeline.json', 
			'display' => __('User timeline'),
			'description' => __('twitter-api-user-timeline-description'),
			'valid-options' => array(
				'count' => array(
					'display' => __('Count'),
					'description' => __('twitter-api-option-count-description'),
					),
				'screen_name' => array(
					'display' => __('Screen name'),
					'description' => __('twitter-api-option-screen_name-description'),
					),
				)
			),
		'home' => array(
			'api-uri' => 'statuses/home_timeline.json', 
			'display' => __('Home timeline'),
			'description' => __('twitter-api-home-timeline-description'),
			'valid-options' => array(
				'count' => array(
					'display' => __('Count'),
					'description' => __('twitter-api-option-count-description'),
					),
				)
			),
		'mentions' => array(
			'api-uri' => 'statuses/mentions_timeline.json', 
			'display' => __('Mentions timeline'),
			'description' => __('twitter-api-mentions-timeline-description'),
			'valid-options' => array(
				'count' => array(
					'display' => __('Count'),
					'description' => __('twitter-api-option-count-description'),
					),
				)
			),
		'search' => array(
			'api-uri' => 'search/tweets.json', 
			'display' => __('Search'),
			'description' => __('twitter-api-search-description'),
			'valid-options' => array(
				'count' => array(
					'display' => __('Count'),
					'description' => __('twitter-api-option-count-description'),
					),
				'q' => array(
					'display' => __('Query'),
					'description' => __('twitter-api-option-query-description'),
					'example' => '@kohana #php'
					),
				),
			'return-index' => 'statuses'
			)
		)
);  