<?php

namespace airmoi\FileMaker\Tests\Support;

use airmoi\FileMaker\FileMaker;

/**
 * FileMaker instance that never hits the network.
 * Queue raw responses with queueCwp()/queueDataApi(); performed requests are recorded.
 */
class FakeFileMaker extends FileMaker
{
    /** @var array<int, array{params: array, grammar: string}> */
    public array $cwpRequests = [];

    /** @var array<int, array> */
    public array $dataApiRequests = [];

    /** @var string[] */
    private array $cwpResponses = [];

    /** @var string[] */
    private array $dataApiResponses = [];

    /** @var string|null Response returned when the CWP queue is empty */
    public ?string $defaultCwpResponse = null;

    public function queueCwp(string ...$responses): static
    {
        array_push($this->cwpResponses, ...$responses);
        return $this;
    }

    public function queueDataApi(string ...$responses): static
    {
        array_push($this->dataApiResponses, ...$responses);
        return $this;
    }

    public function executeCWP($params, $grammar = 'fmresultset')
    {
        $this->cwpRequests[] = ['params' => $params, 'grammar' => $grammar];
        if ($this->cwpResponses) {
            return array_shift($this->cwpResponses);
        }
        if ($this->defaultCwpResponse !== null) {
            return $this->defaultCwpResponse;
        }
        throw new \LogicException('No CWP response queued for ' . json_encode($params));
    }

    public function runDataApiQuery(array $query)
    {
        $this->dataApiRequests[] = $query;
        if (!$this->dataApiResponses && $query['method'] === 'DELETE' && str_ends_with($query['uri'], '/sessions/{sessionToken}')) {
            return Fixtures::dataApi();
        }
        if (!$this->dataApiResponses) {
            throw new \LogicException('No Data API response queued for ' . $query['uri']);
        }
        return array_shift($this->dataApiResponses);
    }

    /**
     * Recorded Data API logout requests
     */
    public function logoutRequests(): array
    {
        return array_values(array_filter(
            $this->dataApiRequests,
            fn($query) => $query['method'] === 'DELETE' && str_ends_with($query['uri'], '/sessions/{sessionToken}')
        ));
    }

    /**
     * Last recorded CWP request params
     */
    public function lastCwpParams(): array
    {
        return end($this->cwpRequests)['params'];
    }
}
