<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

namespace Eventum\SlackUnfurl\Route;

use SlackUnfurl\Route\RouteMatcher;

class EventumRoutes extends RouteMatcher
{
    /** @var string */
    private $domain;

    public function __construct(string $domain)
    {
        $this->domain = $domain;
    }

    protected function getRoutes(): array
    {
        return $this->routes = $this->routes ?: $this->buildRoutes($this->domain);
    }

    /**
     * @param string $domain
     * @return array
     */
    protected function buildRoutes(string $domain): array
    {
        $base = "https?://\Q{$domain}\E";

        return [
            'issue' => "^${base}/view\.php\?id=(?P<id>\d+)",
        ];
    }
}
