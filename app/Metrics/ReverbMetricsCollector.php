<?php

namespace App\Metrics;

use App\Metrics\Contracts\MetricsStore;

class ReverbMetricsCollector
{
    public function __construct(
        protected MetricsStore $store
    ) {}

    /**
     * Record a new connection.
     */
    public function connectionOpened(string $appId): void
    {
        $this->store->increment('reverb_connections_created_total', ['app_id' => $appId]);
        $this->store->incrementGauge('reverb_connections_total', ['app_id' => $appId]);
    }

    /**
     * Record a closed connection.
     */
    public function connectionClosed(string $appId, string $reason = 'normal'): void
    {
        $this->store->increment('reverb_connections_closed_total', ['app_id' => $appId, 'reason' => $reason]);
        $this->store->decrementGauge('reverb_connections_total', ['app_id' => $appId]);
    }

    /**
     * Record a received message.
     */
    public function messageReceived(string $appId, string $channelType = 'public'): void
    {
        $this->store->increment('reverb_messages_received_total', ['app_id' => $appId, 'channel_type' => $channelType]);
    }

    /**
     * Record a sent message.
     */
    public function messageSent(string $appId, string $channelType = 'public'): void
    {
        $this->store->increment('reverb_messages_sent_total', ['app_id' => $appId, 'channel_type' => $channelType]);
    }

    /**
     * Record a channel creation.
     */
    public function channelCreated(string $appId, string $channelType = 'public'): void
    {
        $this->store->incrementGauge('reverb_channels_active', ['app_id' => $appId, 'type' => $channelType]);
    }

    /**
     * Record a channel removal.
     */
    public function channelRemoved(string $appId, string $channelType = 'public'): void
    {
        $this->store->decrementGauge('reverb_channels_active', ['app_id' => $appId, 'type' => $channelType]);
    }

    /**
     * Record a subscription.
     */
    public function subscriptionCreated(string $appId, string $channelType = 'public'): void
    {
        $this->store->increment('reverb_subscriptions_total', ['app_id' => $appId, 'channel_type' => $channelType]);
    }

    /**
     * Set the server info metric.
     */
    public function setServerInfo(string $instance, string $version): void
    {
        $this->store->gauge('reverb_server_info', 1, ['instance' => $instance, 'version' => $version]);
    }

    /**
     * Get the underlying metrics store.
     */
    public function getStore(): MetricsStore
    {
        return $this->store;
    }
}
