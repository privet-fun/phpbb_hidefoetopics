<?php

/**
 *
 * Hide Foe Topics extension
 *
 * @copyright (c) 2023, privet.fun, https://privet.fun
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'L_PRIVET_HIDEFOETOPICS'	=> 'Скрыть темы, созданные недругами',
));

