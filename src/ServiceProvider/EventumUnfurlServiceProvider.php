<?php

namespace Eventum\SlackUnfurl\ServiceProvider;

use Eventum\SlackUnfurl\Event\Subscriber\EventumUnfurler;
use Eventum_RPC;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventumUnfurlServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['eventum.rpc_url'] = getenv('EVENTUM_RPC_URL');
        $app['eventum.username'] = getenv('EVENTUM_USERNAME');
        $app['eventum.access_token'] = getenv('EVENTUM_ACCESS_TOKEN');
        $app['eventum.domain'] = getenv('EVENTUM_DOMAIN');
        $app['eventum.timezone'] = getenv('TIMEZONE');

        $app[Eventum_RPC::class] = function ($app) {
            $client = new Eventum_RPC($app['eventum.rpc_url']);
            $client->setCredentials($app['eventum.username'], $app['eventum.access_token']);

            return $client;
        };

        $app[EventumUnfurler::class] = function ($app) {
            return new EventumUnfurler(
                $app[Eventum_RPC::class],
                $app['eventum.domain'],
                $app['eventum.timezone'],
                $app['logger']
            );
        };

        $app->extend('unfurl.dispatcher', function (EventDispatcherInterface $dispatcher, $app) {
            $dispatcher->addSubscriber($app[EventumUnfurler::class]);

            return $dispatcher;
        });
    }
}