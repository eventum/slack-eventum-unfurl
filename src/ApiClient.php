<?php

namespace Eventum\SlackUnfurl;

use Eventum\RPC\EventumXmlRpcClient;
use Eventum\RPC\XmlRpcException;

class ApiClient
{
    /** @var EventumXmlRpcClient */
    private $client;

    public function __construct(EventumXmlRpcClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get issue details, but filter only needed keys.
     *
     * @param int $issueId
     * @throws XmlRpcException
     * @return array
     */
    public function getIssueDetails(int $issueId): array
    {
        /** @var array $issue */
        $issue = $this->client->getIssueDetails($issueId);

        $fields = [
            'assignments',
            'iss_created_date',
            'iss_created_date_ts',
            'iss_description',
            'iss_id',
            'iss_last_internal_action_date',
            'iss_last_public_action_date',
            'iss_original_description',
            'iss_summary',
            'iss_updated_date',
            'prc_title',
            'pri_title',
            'reporter',
            'sta_title',
        ];

        return array_intersect_key($issue, array_flip($fields));
    }

    /**
     * @param int $noteId
     * @throws XmlRpcException
     * @return array
     */
    public function getNoteDetails(int $noteId): array
    {
        $fields = [
            'not_id',
            'not_iss_id',
            'not_title',
            'not_from',
            'not_note',
            'not_created_date_ts',
        ];

        return $this->client->getNoteDetails($noteId, $fields);
    }
}
