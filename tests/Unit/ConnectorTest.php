<?php

namespace mdlutz24\mflconnector\tests\Unit;

use mdlutz24\mflconnector\Connector;
use PHPUnit\Framework\TestCase;

/**
 * Tests the MFL Connector class
 *
 * @coversDefaultClass \mdlutz24\mflconnector\Connector
 */
class ConnectorTest extends TestCase {

  /**
   * @covers ::getHostFromLeagueId
   */
  public function testGetHostFromLeagueId() {
    $host = Connector::getHostFromLeagueId("46324", "2019");
    $this->assertEquals('www71.myfantasyleague.com', $host);
  }

  public function testGetPlayers() {
    $connector = new Connector('46324', 'www71.myfantasyleague.com');
    $players = $connector->getPlayers();
  }
}
