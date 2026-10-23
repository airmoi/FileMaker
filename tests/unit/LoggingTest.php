<?php

namespace airmoi\FileMaker\Tests\Unit;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\Helpers\DataApi;
use airmoi\FileMaker\Tests\Support\FakeFileMaker;
use PHPUnit\Framework\TestCase;

/**
 * @see https://github.com/airmoi/FileMaker/issues/42
 */
class LoggingTest extends TestCase
{
    private function logger(): object
    {
        return new class {
            public array $messages = [];
            public function log($message, $level)
            {
                $this->messages[] = [$level, $message];
            }
        };
    }

    public static function levelProvider(): array
    {
        return [
            'default (errors only)' => [null, [FileMaker::LOG_ERR]],
            'int' => [3, [FileMaker::LOG_ERR]],
            'numeric string' => ['3', [FileMaker::LOG_ERR]],
            'notice' => [FileMaker::LOG_NOTICE, [FileMaker::LOG_ERR, FileMaker::LOG_NOTICE]],
            'debug' => [FileMaker::LOG_DEBUG, [FileMaker::LOG_ERR, FileMaker::LOG_NOTICE, FileMaker::LOG_INFO, FileMaker::LOG_DEBUG]],
            'disabled' => [false, []],
        ];
    }

    /**
     * @dataProvider levelProvider
     */
    public function testMessagesAreFilteredByLogLevel($logLevel, array $expectedLevels): void
    {
        $options = $logLevel === null ? [] : ['logLevel' => $logLevel];
        $fm = new FakeFileMaker('test', null, null, null, $options);
        $logger = $this->logger();
        $fm->setLogger($logger);

        foreach ([FileMaker::LOG_ERR, FileMaker::LOG_NOTICE, FileMaker::LOG_INFO, FileMaker::LOG_DEBUG] as $level) {
            $fm->log('message', $level);
        }
        $this->assertSame($expectedLevels, array_column($logger->messages, 0));
    }

    public function testNothingIsLoggedWithoutLogger(): void
    {
        $fm = new FakeFileMaker('test', null, null, null, ['logLevel' => FileMaker::LOG_DEBUG]);
        $this->expectOutputString('');
        $fm->log('message', FileMaker::LOG_DEBUG);
    }

    public function testDataApiCredentialsAndTokensAreMasked(): void
    {
        $fm = new class ('test', 'https://fms.example', 'admin', 'Sup3rSecret') extends FakeFileMaker {
            public function describe(string $uri, array $query): string
            {
                return $this->describeDataApiRequest($uri, $query);
            }
        };

        $login = [
            'params' => ['database' => 'test'],
            'body' => ['fmDataSource' => [['database' => 'test', 'username' => 'admin', 'password' => 'Sup3rSecret']]],
        ];
        $description = $fm->describe('https://fms.example/fmi/data/vLatest/databases/test/sessions', $login);
        $this->assertStringNotContainsString('Sup3rSecret', $description);
        $this->assertStringContainsString('"password":"***"', $description);

        $logout = ['params' => ['database' => 'test', 'sessionToken' => 'abc+123'], 'body' => null];
        $description = $fm->describe('https://fms.example/fmi/data/vLatest/databases/test/sessions/' . rawurlencode('abc+123'), $logout);
        $this->assertSame('https://fms.example/fmi/data/vLatest/databases/test/sessions/***', $description);
    }

    public function testDataApiFindBodyIsLogged(): void
    {
        $fm = new class ('test', 'https://fms.example', 'admin', 'secret') extends FakeFileMaker {
            public function describe(string $uri, array $query): string
            {
                return $this->describeDataApiRequest($uri, $query);
            }
        };
        $query = DataApi::prepareQuery(['-db' => 'test', '-lay' => 'sample', '-find' => true, 'name' => 'foo']);
        $this->assertStringContainsString('Body: {"query":[{"name":"foo"}]}', $fm->describe('uri', $query));
    }
}
