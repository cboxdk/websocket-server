<?php

namespace App\Listeners;

use App\Metrics\ReverbMetricsCollector;
use Laravel\Reverb\Events\ChannelCreated;
use Laravel\Reverb\Events\ChannelRemoved;
use Laravel\Reverb\Events\ConnectionPruned;
use Laravel\Reverb\Events\MessageReceived;
use Laravel\Reverb\Events\MessageSent;

class ReverbMetricsListener
{
    public function __construct(
        protected ReverbMetricsCollector $collector
    ) {}

    /**
     * Handle the MessageReceived event.
     */
    public function handleMessageReceived(MessageReceived $event): void
    {
        $appId = $event->connection->app()->id();
        $channelType = $this->determineChannelTypeFromMessage($event->message);

        $this->collector->messageReceived($appId, $channelType);
    }

    /**
     * Handle the MessageSent event.
     */
    public function handleMessageSent(MessageSent $event): void
    {
        $appId = $event->connection->app()->id();
        $channelType = $this->determineChannelTypeFromMessage($event->message);

        $this->collector->messageSent($appId, $channelType);
    }

    /**
     * Handle the ChannelCreated event.
     */
    public function handleChannelCreated(ChannelCreated $event): void
    {
        $channelName = $event->channel->name();
        $channelType = $this->determineChannelType($channelName);

        // Get app ID from the first connection if available
        $connections = $event->channel->connections();
        $firstConnection = reset($connections);
        $appId = $firstConnection ? $firstConnection->app()->id() : 'unknown';

        $this->collector->channelCreated($appId, $channelType);
    }

    /**
     * Handle the ChannelRemoved event.
     */
    public function handleChannelRemoved(ChannelRemoved $event): void
    {
        $channelName = $event->channel->name();
        $channelType = $this->determineChannelType($channelName);

        // Get app ID from the channel context - this is tricky since connections may be empty
        // We'll use 'unknown' if we can't determine it
        $connections = $event->channel->connections();
        $firstConnection = reset($connections);
        $appId = $firstConnection ? $firstConnection->app()->id() : 'unknown';

        $this->collector->channelRemoved($appId, $channelType);
    }

    /**
     * Handle the ConnectionPruned event.
     */
    public function handleConnectionPruned(ConnectionPruned $event): void
    {
        $appId = $event->connection->app()->id();

        $this->collector->connectionClosed($appId, 'pruned');
    }

    /**
     * Determine the channel type from the channel name.
     */
    protected function determineChannelType(string $channelName): string
    {
        if (str_starts_with($channelName, 'private-encrypted-')) {
            return 'encrypted';
        }

        if (str_starts_with($channelName, 'private-')) {
            return 'private';
        }

        if (str_starts_with($channelName, 'presence-')) {
            return 'presence';
        }

        return 'public';
    }

    /**
     * Determine the channel type from a message payload.
     */
    protected function determineChannelTypeFromMessage(string $message): string
    {
        try {
            $data = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

            if (isset($data['channel'])) {
                return $this->determineChannelType($data['channel']);
            }
        } catch (\JsonException) {
            // Ignore JSON parse errors
        }

        return 'public';
    }

    /**
     * Subscribe to Reverb events.
     *
     * @return array<class-string, string>
     */
    public function subscribe(\Illuminate\Events\Dispatcher $events): array
    {
        return [
            MessageReceived::class => 'handleMessageReceived',
            MessageSent::class => 'handleMessageSent',
            ChannelCreated::class => 'handleChannelCreated',
            ChannelRemoved::class => 'handleChannelRemoved',
            ConnectionPruned::class => 'handleConnectionPruned',
        ];
    }
}
