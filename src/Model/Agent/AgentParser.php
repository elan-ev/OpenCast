<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Agent;

use DateTimeImmutable;
use Exception;
use stdClass;

class AgentParser
{
    /**
     * @return Agent[]
     * @throws Exception
     */
    public function parseApiResponse(array $response): array
    {
        return array_map(fn(stdClass $item): Agent => new Agent(
            $item->agent_id,
            $item->status,
            $item->inputs,
            new DateTimeImmutable($item->update),
            $item->url
        ), $response);
    }
}
