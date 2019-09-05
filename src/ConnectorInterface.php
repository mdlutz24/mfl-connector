<?php

namespace mdlutz24\mflconnector;

interface ConnectorInterface {

  const AUTH_COOKIE = 'MFL_USER_ID';

  const DETAILS = 'DETAILS';
  const JSON = 'JSON';
  const LEAGUE_ID = 'L';
  const PLAYERS = 'PLAYERS';
  const SINCE = 'SINCE';
  const TYPE = 'TYPE';
  const WEEK = 'W';

  public function makeCall($command = 'export', $args = []);

  public function export($type, array $args);

  public function import($type, array $args);

  public function getPlayers($since = 0, array $players = [], bool $details = FALSE);
}