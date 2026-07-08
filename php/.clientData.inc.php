<?php
$config = array_merge(
  parse_ini_file(__DIR__ . "/configs/config.ini", true),
  parse_ini_file(__DIR__ . "/configs/local.ini", true)
);
if (($_SERVER["SERVER_NAME"] ?? "localhost") === "wulfkg.com") {
  $config = array_merge(
    parse_ini_file(__DIR__ . "/configs/config.ini", true),
  );
}

define("DB_HOST", $config["database"]["host"]);
define("DB_USER", $config["database"]["user"]);
define("DB_PASSWORD", $config["database"]["pass"]);
define("DB_NAME", $config["database"]["name"]);
define("DEBUG", $config["debug"]);
define("CLIENT_DATA", $config["client_data"]);
