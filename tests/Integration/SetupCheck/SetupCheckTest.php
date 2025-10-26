<?php

namespace Integration\SetupCheck;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SetupCheckTest extends WebTestCase
{
    public function testConnectivity(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/setup-check');
        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $this->assertJson($response->getContent());
        $responseData = json_decode($response->getContent(), TRUE);
        $this->assertArrayHasKey('ok', $responseData);
        $this->assertTrue($responseData['ok']);
    }


}