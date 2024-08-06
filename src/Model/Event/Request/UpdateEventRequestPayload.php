<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\WorkflowParameter\Processing;

class UpdateEventRequestPayload implements JsonSerializable
{
    public function __construct(protected ?Metadata $metadata, protected ?ACL $acl = null, protected ?Scheduling $scheduling = null, protected ?Processing $processing = null)
    {
    }

    public function jsonSerialize(): mixed
    {
        $data = [];
        if (!is_null($this->metadata)) {
            $data['metadata'] = json_encode([$this->metadata->jsonSerialize()]);
        }
        if (!is_null($this->acl)) {
            $data['acl'] = json_encode($this->acl);
        }
        if (!is_null($this->scheduling)) {
            $data['scheduling'] = json_encode($this->scheduling);
        }
        if (!is_null($this->processing)) {
            $data['processing'] = json_encode($this->processing);
        }
        return $data;
    }
}
