<?php

session_start();

define('CONFIG_FILE', 'config.json');
define('MAGIC_PARAMETER', '_json_demo');
define('SESSION_STATE_KEY', 'json_demo');

require_once('json-utils.php');
require_once('match-uri-template.php');

$config = json_decode(file_get_contents(CONFIG_FILE));

$action = NULL;
if (isset($_GET[MAGIC_PARAMETER])) {
	$action = $_GET[MAGIC_PARAMETER];
}
if ($action == "state") {
	json_exit((object)$_SESSION[SESSION_STATE_KEY]);
}
if ($action == "reset") {
	$_SESSION[SESSION_STATE_KEY] = array();
	json_exit("Session state reset");
}
if ($action == "view") {
	json_exit($config);
}

function matchConditions ($where) {
	if ($where->method) {
		$method = $_SERVER['REQUEST_METHOD'];
		if (is_array($where->method)) {
			if (!in_array($method, $where->method)) {
				return FALSE;
			}
		} else if ($method != $where->method) {
			return FALSE;
		}
	}
	if ($where->path) {
		if (!($params = matchUriTemplate($where->path))) {
			return FALSE;
		}
	}
	if ($where->query) {
		foreach ($where->query as $key => $value) {
			if (!isset($_GET[$key]) || $_GET[$key] != $value) {
				return FALSE;
			}
		}
	}
	if ($where->state) {
		foreach ($where->state as $key => $value) {
			if (!isset($_SESSION[SESSION_STATE_KEY][$key]) || $_SESSION[SESSION_STATE_KEY][$key] != $value) {
				return FALSE;
			}
		}
	}
	return TRUE;
}

foreach ($config->matches as $match) {
	if (!matchConditions($match->where)) {
		continue;
	}
	if ($match->state) {
		foreach ($match->state as $key => $value) {
			$_SESSION[SESSION_STATE_KEY][$key] = $value;
		}
	}
	if ($match->headers) {
		foreach ($match->headers as $name => $value) {
			if (!is_array($value)) {
				$value = array($value);
			}
			foreach ($value as $idx => $entry) {
				header ($name.": ".$entry, $idx == 0);
			}
		}
	}
	json_exit($match->data, $match->schema);
}

json_error(404, "Page not found");

?>