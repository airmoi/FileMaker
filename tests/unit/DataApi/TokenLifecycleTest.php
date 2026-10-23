<?php

namespace airmoi\FileMaker\Tests\Unit\DataApi;

use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Tests\Support\ArraySessionHandler;
use airmoi\FileMaker\Tests\Support\FakeFileMaker;
use airmoi\FileMaker\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * @see https://github.com/airmoi/FileMaker/issues/56
 */
class TokenLifecycleTest extends TestCase
{
    private function fm(?ArraySessionHandler $session = null, array $options = []): FakeFileMaker
    {
        $fm = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret', ['useDataApi' => true] + $options);
        if ($session) {
            $fm->setSessionHandler($session);
        }
        return $fm;
    }

    private function loginCount(FakeFileMaker $fm): int
    {
        return count(array_filter($fm->dataApiRequests, fn($q) => $q['method'] === 'POST' && str_ends_with($q['uri'], '/sessions')));
    }

    public function testSetSessionHandlerIsApplied(): void
    {
        $session = new ArraySessionHandler();
        $fm = $this->fm($session);
        $this->assertSame($session, $fm->sessionHandler);
        $this->assertSame($session, $fm->getProperty('sessionHandler'));
    }

    public function testSetCacheIsApplied(): void
    {
        $cache = new class {
            public function get($key) { return null; }
            public function set($key, $value, $duration) { return true; }
        };
        $fm = $this->fm();
        $fm->setCache($cache);
        $this->assertSame($cache, $fm->getProperty('cache'));
    }

    public function testTokenIsSavedAndReusedThroughSessionHandler(): void
    {
        $session = new ArraySessionHandler();

        $first = $this->fm($session);
        $first->queueDataApi(Fixtures::dataApi(['token' => 'TOKEN']), Fixtures::dataApi(['layouts' => []]));
        $first->listLayouts();
        $this->assertTrue($first->isTokenPersisted());
        $this->assertContains('TOKEN', $session->data);
        unset($first);

        // Next request: token is reused, no new login, no logout
        $second = $this->fm($session);
        $second->queueDataApi(Fixtures::dataApi(['layouts' => []]));
        $second->listLayouts();
        $this->assertSame(0, $this->loginCount($second));
        $this->assertContains('Authorization: Bearer TOKEN', $second->dataApiRequests[0]['headers']);
        $second->__destruct();
        $this->assertCount(0, $second->logoutRequests());
    }

    public function testTokenIsLoggedOutOnDestructWhenItCannotBeSaved(): void
    {
        // CLI without session handler: the token can't be reused, it must be released
        $fm = $this->fm();
        $fm->queueDataApi(Fixtures::dataApi(['token' => 'TOKEN']), Fixtures::dataApi(['layouts' => []]));
        $fm->listLayouts();
        $this->assertFalse($fm->isTokenPersisted());

        $fm->__destruct();
        $logouts = $fm->logoutRequests();
        $this->assertCount(1, $logouts);
        $this->assertSame('TOKEN', $logouts[0]['params']['sessionToken']);
        $this->assertNull($fm->getProperty('token'));

        // a second destruct must not logout again
        $fm->__destruct();
        $this->assertCount(1, $fm->logoutRequests());
    }

    public function testDestructDoesNothingWithoutDataApi(): void
    {
        $fm = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret');
        $fm->__destruct();
        $this->assertSame([], $fm->dataApiRequests);
    }

    public function testDestructIgnoresLogoutErrors(): void
    {
        $fm = $this->fm();
        $fm->setToken('EXPIRED');
        $fm->queueDataApi(Fixtures::dataApi([], 952, 'Invalid FileMaker Data API token'));
        $fm->__destruct();
        $this->assertNull($fm->getProperty('token'));
    }

    public function testExplicitLogoutClearsSavedToken(): void
    {
        $session = new ArraySessionHandler();
        $fm = $this->fm($session);
        $fm->queueDataApi(Fixtures::dataApi(['token' => 'TOKEN']), Fixtures::dataApi(['layouts' => []]));
        $fm->listLayouts();

        $this->assertTrue($fm->logout());
        $this->assertCount(1, $fm->logoutRequests());
        $this->assertNotContains('TOKEN', $session->data);
        $this->assertFalse($fm->isTokenPersisted());
    }

    public function testExpiredTokenIsRenewedAndReplaced(): void
    {
        $session = new ArraySessionHandler();
        $fm = $this->fm($session);
        $fm->setToken('OLD');
        $fm->queueDataApi(
            Fixtures::dataApi([], 952, 'Invalid FileMaker Data API token'),
            Fixtures::dataApi(['token' => 'NEW']),
            Fixtures::dataApi(['layouts' => []])
        );
        $fm->listLayouts();

        $this->assertSame('NEW', $fm->getProperty('token'));
        $this->assertContains('NEW', $session->data);
        $this->assertNotContains('OLD', $session->data);
        $this->assertContains('Authorization: Bearer NEW', end($fm->dataApiRequests)['headers']);
    }

    public function testLoginErrorIsReturnedWithDefaultErrorHandling(): void
    {
        $fm = $this->fm(null, ['errorHandling' => 'default']);
        $fm->queueDataApi(Fixtures::dataApi([], 212, 'Invalid user account and/or password'));
        $result = $fm->listLayouts();
        $this->assertInstanceOf(FileMakerException::class, $result);
        $this->assertSame(212, $result->getCode());
        $this->assertCount(1, $fm->dataApiRequests);
    }

    public function testLoginErrorThrows(): void
    {
        $fm = $this->fm();
        $fm->queueDataApi(Fixtures::dataApi([], 212, 'Invalid user account and/or password'));
        $this->expectException(FileMakerException::class);
        $fm->listLayouts();
    }
}
