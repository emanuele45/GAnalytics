<?php
/**
 * Google Analytics tracking
 *
 * @author emanuele
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.4
 */

class GAnalytics_Integrate
{
	/**
	 * @var string
	 */
	const TRACKREGEX = 'UA-\d{7,9}-\d{1,2}';

	public function __construct()
	{
	}

	public static function load_theme()
	{
		global $modSettings, $user_info;

		// No tracking code on xml or api requests
		if (isset($_REQUEST['xml']) || isset($_REQUEST['api']))
		{
			return;
		}

		// As well as if the tracking code is not set
		if (empty($modSettings['ga_tracking_code']))
		{
			return;
		}

		if (!empty($modSettings['ga_tracking_only_guests']) && !$user_info['is_guest'])
		{
			return;
		}

		if (empty($modSettings['ga_tracking_adminpages']) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'admin')
		{
			return;
		}

		addInlineJavascript('
	window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;
	ga(\'create\', \'' . $modSettings['ga_tracking_code'] . '\', \'auto\');
	ga(\'send\', \'pageview\');', true);

		loadJavascriptFile('https://www.google-analytics.com/analytics.js', array('defer' => true, 'async' => 'true'));
	}

	public static function general_mod_settings(&$config_vars)
	{
		global $txt;

		loadLanguage('GAnalytics');
		$config_vars[] = array('text', 'ga_tracking_code', 'subtext' => $txt['ga_tracking_code_description']);
		$config_vars[] = array('check', 'ga_tracking_adminpages');
		$config_vars[] = array('check', 'ga_tracking_only_guests');

		addInlineJavascript('
			$(document).ready(function() {
				$("#ga_tracking_code").change(function() {
					var val = $(this).val();
					if (val.indexOf("<script") !== -1)
					{
						var regex = /' . self::TRACKREGEX . '/,
							match = regex.exec(val);

						$(this).val(match);
					}
				});
			});', true);
	}

	public static function save_general_mod_settings()
	{
		if (!empty($_POST['ga_tracking_code']))
		{
			// I doubt this will ever happen, because Elk strips the script tag
			if (strpos('<script', $_POST['ga_tracking_code']) !== false)
			{
				$clean_tracking = self::extractUA($_POST['ga_tracking_code']);
			}
			else
			{
				$clean_tracking = preg_replace('~[^\w\d\-]~', '', $_POST['ga_tracking_code']);
			}

			if (preg_match('~^' . self::TRACKREGEX . '$~', $clean_tracking) === 0)
			{
				unset($_POST['ga_tracking_code']);
			}
			else
			{
				$_POST['ga_tracking_code'] = $clean_tracking;
			}
		}
	}

	protected static function extractUA($val)
	{
		preg_match('~' . self::TRACKREGEX . '~', $val, $matches);

		if (isset($matches[1]))
		{
			return $matches[1];
		}
		else
		{
			return '';
		}
	}
}
