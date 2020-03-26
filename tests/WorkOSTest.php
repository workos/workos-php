<?php

namespace WorkOS;

use PHPUnit\Framework\TestCase;

class WorkOSTest extends TestCase
{
  public function testApiKeySet()
  {
    $apiKey = "key";

    WorkOS::setApiKey($apiKey);

    $this->assertEquals(WorkOS::getApiKey(), $apiKey);
  }
}