<?php

namespace airmoi\FileMaker\Tests\Unit\DataApi;

use airmoi\FileMaker\Tests\Support\ArraySessionHandler;
use airmoi\FileMaker\Tests\Support\FakeFileMaker;
use airmoi\FileMaker\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * FileMaker Data API rejects lowercase "basic"/"bearer" schemes
 * @see https://github.com/airmoi/FileMaker/issues/54
 */
class AuthorizationHeaderTest extends TestCase
{
    public function testLoginUsesBasicAndQueriesUseBearer(): void
    {
        $fm = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret', ['useDataApi' => true]);
        $fm->setSessionHandler(new ArraySessionHandler());
        $fm->queueDataApi(
            Fixtures::dataApi(['token' => 'TOKEN']),
            Fixtures::dataApi(['layouts' => []])
        );

        $fm->listLayouts();

        [$login, $query] = $fm->dataApiRequests;
        $this->assertContains('Authorization: Basic ' . base64_encode('admin:secret'), $login['headers']);
        $this->assertContains('Authorization: Bearer TOKEN', $query['headers']);
    }

    public function testListDatabasesUsesBasic(): void
    {
        $fm = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret', ['useDataApi' => true]);
        $fm->queueDataApi(Fixtures::dataApi(['databases' => [['name' => 'test']]]));

        $this->assertSame(['test'], $fm->listDatabases());
        $this->assertContains('Authorization: Basic ' . base64_encode('admin:secret'), $fm->dataApiRequests[0]['headers']);
    }
}
