<?php

namespace airmoi\FileMaker\Tests\Unit\Object;

use airmoi\FileMaker\Object\Layout;
use airmoi\FileMaker\Tests\Support\FakeFileMaker;
use airmoi\FileMaker\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * Cached layouts must not leak the FileMaker instance (settings, engine, credentials) that loaded them
 */
class LayoutCacheTest extends TestCase
{
    private function related(): string
    {
        return Fixtures::fmResultSet([], [3 => ['id' => 3, 'name' => 'foo', 'code' => 'x']]);
    }

    public function testCachedLayoutIsBoundToRequestingInstance(): void
    {
        $first = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret');
        $second = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret');
        $first->queueCwp(Fixtures::fmResultSet());
        $second->defaultCwpResponse = $this->related();

        $first->getLayout('sample');
        $layout = $second->getLayout('sample');

        $this->assertCount(1, $first->cwpRequests);
        $this->assertCount(0, $second->cwpRequests, 'layout should be served from cache');
        $this->assertSame($second, $layout->fm);
        foreach ($layout->getFields() as $field) {
            $this->assertSame($second, $field->getLayout()->fm);
        }
        $this->assertSame($first, $first->getLayout('sample')->fm);

        $record = $second->createRecord('sample', ['name' => 'foo', 'code' => 'x']);
        $record->commit();
        $this->assertCount(1, $first->cwpRequests, 'record must be committed through its own instance');
        $this->assertArrayHasKey('-new', $second->lastCwpParams());
    }

    public function testCacheIsEngineSpecific(): void
    {
        $cwp = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret');
        $dataApi = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret', ['useDataApi' => true]);
        $cwp->queueCwp(Fixtures::fmResultSet());
        $cwp->getLayout('sample');

        $dataApi->queueDataApi(Fixtures::dataApi(['token' => 'T']), Fixtures::dataApi([], 105, 'Layout is missing'));
        $this->expectExceptionCode(105);
        $dataApi->getLayout('sample');
    }

    public function testExternalCacheDoesNotStoreCredentials(): void
    {
        $cache = new class {
            public array $data = [];
            public function get($key) { return isset($this->data[$key]) ? unserialize($this->data[$key]) : false; }
            public function set($key, $value, $duration) { $this->data[$key] = serialize($value); return true; }
        };

        $first = new FakeFileMaker('test', 'https://fms.example', 'admin', 'Sup3rSecret');
        $second = new FakeFileMaker('test', 'https://fms.example', 'admin', 'Sup3rSecret');
        $first->setCache($cache);
        $second->setCache($cache);
        $first->queueCwp(Fixtures::fmResultSet());
        $first->getLayout('sample');

        $this->assertNotEmpty($cache->data);
        foreach ($cache->data as $serialized) {
            $this->assertStringNotContainsString('Sup3rSecret', $serialized);
        }

        $layout = $second->getLayout('sample');
        $this->assertInstanceOf(Layout::class, $layout);
        $this->assertCount(0, $second->cwpRequests);
        $this->assertSame($second, $layout->fm);
        $this->assertSame($second, $layout->getField('id')->getLayout()->fm);
        $this->assertTrue($layout->getField('id')->isAutoEntered());
    }
}
