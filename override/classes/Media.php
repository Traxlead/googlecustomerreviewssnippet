<?php
/**
 * This files comes from https://gist.github.com/hereswhatidid/8c8edef106ee95138b03
 * It allows to keep javascript in line when specified with the tag "data-keepinline"
 */
Class Media extends MediaCore
{
	public static function deferScript($matches)
	{
		if (!is_array($matches))
			return false;
		$inline = '';

		if (isset($matches[0]))
			$original = trim($matches[0]);

		if (isset($matches[1]))
			$inline = trim($matches[1]);

		/* This is an inline script, add its content to inline scripts stack then remove it from content */
		if (!empty($inline) && preg_match('/<\s*script(?!.*data-keepinline)[^>]*>/ims', $original) !== 0 && Media::$inline_script[] = $inline)
			return '';

		/* This is an external script, if it already belongs to js_files then remove it from content */
		preg_match('/src\s*=\s*["\']?([^"\']*)[^>]/ims', $original, $results);
		if (array_key_exists(1, $results))
		{
			if (substr($results[1], 0, 2) == '//')
			{
				$protocol_link = Tools::getCurrentUrlProtocolPrefix();
				$results[1] = $protocol_link.ltrim($results[1], '/');
			}
			if (preg_match('/<\s*script(?!.*data-keepinline)[^>]*>/ims', $original) !== 0) {
				if (in_array($results[1], Context::getContext()->controller->js_files) || in_array($results[1], Media::$inline_script_src))
					return '';
			} else {
				Context::getContext()->controller->removeJS($results[1]);
			}
		}

		/* return original string because no match was found */
		return $original;
	}
}
?>