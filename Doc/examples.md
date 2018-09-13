# Examples


## Example 1: Full configuration file example

```yaml
m6web_statsd_prometheus:
    servers:
        default:
            address: 'udp://localhost'
            port: 1236
    tags:
        tagA: 'tagValue1'
        tagB: 'tagValue2'
    clients: #clients
        default:
            max_queued_metrics: 10000 #optional
            server: 'default'
            groups: #groups
                groupA:
                    tags:
                        tagC: 'tagValue3'
                        tagD: 'tagValue4'
                    events: #events types
                        eventName1:
                            metrics:
                                -   type: 'counter'
                                    name: 'metric_name'
                                    param_value: 'counterValue'
                                    tags:
                                        tagE: ~
                                        tagF: ~
                        eventName2:
                            flush_metrics_queue: true
                            metrics:
                                -   type: 'gauge'
                                    name: 'metric_name2'
                                    param_value: 'gaugeValue'
                                -   type: 'increment'
                                    name: 'metric_name21_some_option'
                groupB:
                    tags:
                        tagE: 'tagValue5'
                        tagF: 'tagValue6'
                    events: #events types
                        eventName3:
                            metrics:
                                -   type: 'increment'
                                    name: 'metric_name3'
                        eventName4:
                            metrics:
                                -   type: 'decrement'
                                    name: 'metric_name4'
                                    tags:
                                        tagE: ~
                        eventName5:
                            metrics:
                                -   type: 'timer'
                                    name: 'metric_name5'               
```

## Example 2: Sending an increment or decrement metric

Configuration example:

```yaml
event1:
    metrics:
        -
          type: 'increment'
          name: 'http_200'
          tags: 
              myTagLabel1: ~
              myTagLabel2: ~
```
This is the code you can send:
```php
// Without sending the tags (they'll be ignored)
$eventDispatcher->dispatch('event1', new MonitoringEvent());

// With sending the tags
$eventDispatcher->dispatch('event1', new MonitoringEvent([
    'myTagLabel1' => 'myTag_value1',
    'myTagLabel2' => 'myTag_value2',
]));
```

## Example 3: Sending a counter, gauge or timing metric

Configuration example:

```yaml
event1:
    metrics:
        -
          type: 'counter'
          name: 'http_200'
          param_value: 'myCounterValue'
          tags: 
              myTagLabel1: ~
              myTagLabel2: ~
```
This is the code you can send:
```php
// Without sending the tags (they'll be ignored)
$eventDispatcher->dispatch('event1', new MonitoringEvent([
    'myCounterValue' => 1234,
]));

// With sending the tags
$eventDispatcher->dispatch('event1', new MonitoringEvent([   
    'myCounterValue' => 1234,
    'myTagLabel1' => 'myTag_value1',
    'myTagLabel2' => 'myTag_value2',
]));
```

## Example 4: Sending multiple metrics in one event

In the config file, you can define:
```yaml
event1:
    metrics:
        -   type: 'increment'
            name: 'number_of_executed_queries'
            tags:
                customTag1: ~
        -   type: 'timing'
            name: 'queries_time_spent'
            param_value: 'executionTimeValue'
            tags:
                customTag2: ~
        -   type: 'counter'
            name: 'number_of_results'
            param_value: 'numberOfResults'                        
```

This is the event definition you can dispatch:
```php
// Without sending the tags (they will be ignored)
$eventDispatcher->dispatch('event1', new MonitoringEvent([
    'executionTimeValue' => 1234,
    'numberOfResults' => 42,
]));

// With sending the tags
$eventDispatcher->dispatch('event1', new MonitoringEvent([    
    'executionTimeValue' => 1234,
    'numberOfResults' => 42,
    'customTag1' => 'the tag1 value',
    'customTag2' => 'the tag2 value',
]));
```

## Example 5: Replacing placeholders (in the metric name)

You cannot use dynamic values in your metric name anymore. You have to use tags.

Old placeholders functions will be working as tags.

In the config file, you can define:
```yaml
event1:
    metrics:
        -
          type: 'increment'
          name: 'request'
          tags:
              status: 'statusCode'
        -
          type: 'counter'
          name: 'country_view'
          param_value: 'countryCounter'
          tags:
               country: ~
```

This is the event definition you can dispatch (the new way):
```php
// Without sending the tags (they will be ignored)
$eventDispatcher->dispatch('event1', new MonitoringEvent([
    'statusCode' => $this->getHttpCode(),
    'country' => 'france',
    'countryCounter' => $this->getCountryCounter(),
]));

// With sending the tags
$eventDispatcher->dispatch('event1', new MonitoringEvent([
    'code' => $this->getHttpCode(),
    'country' => 'france',
    'countryCounter' => $this->getCountryCounter(),
    'customTag1' => 'the tag1 value',
]));
```

This is the event definition you can dispatch (old-fashioned way):
```php
// Without sending the tags (they will be ignored)
$eventDispatcher->dispatch('event1', new CustomEvent(
    $this->getHttpCode(),
    'france',
    $this->getCountryCounter()
));

// CustomEvent.php
class CustomEvent {

    private $code;
    private $country;
    private $countryCounter;

    public function __construct($code, $country, $countryCounter) 
    {
        $this->code = $code;
        $this->country = $country;
        $this->countryCounter = $countryCounter;
    }
    
    public function getStatusCode()
    {
       return $this->code;
    }
    
    public function getCountry()
    {
       return $this->country;
    }
    
    public function getCountryCounter()
    {
       return $this->countryCounter;
    }
}
```
## Example 6: transforming a configuration file (service-6play-users case)

Old one : 

```yaml
m6_statsd:
    servers:
        default:
            address: 'udp://%statsd.address%'
            port: '%statsd.port%'
    console_events: true
    clients:
        default:
            servers: ['all']
            events:
                uniqu_anonymous_id:
                    increment:     service.service-6play-users.uniqanonymousid.<platform>
                m6kernel.terminate:
                    increment:     request.service-6play-users.<status_code>.<route_name>
                    timing:        request.service-6play-users.<status_code>.<route_name>
                    custom_timing: { node: memory.service-6play-users.<status_code>.<route_name>, method: getMemory }
                m6kernel.exception:
                    increment: errors.<status_code>.service-6play-users
                m6video.user_box.pairing:
                    increment: service.service-6play-users.user_box.<platformCode>.<status>
                m6video.auto_pairing:
                    increment: service.service-6play-users.auto_pairing.<network>.<status>
                m6video.redis.list.size:
                    timing: service.service-6play-users.<customer>.command.<command>.redis.list.size
                m6video.command.clean_deleted_tests:
                    increment: service.service-6play-users.command.clean_deleted_tests.<value>
                    immediate_send: true
                m6video.command.export_user_data.send.error:
                    increment: service.service-6play-users.<customer>.command.export_user_data.send.error
                m6video.command.client.error:
                    increment: service.service-6play-users.<customer>.command.<command>.send.error.<value>
                m6video.command.redis.error:
                    increment: service.service-6play-users.<customer>.command.<command>.redis.error
                m6video.command.import_user_data.client.error:
                    increment: service.service-6play-users.command.import_user_data.error.<value>
                m6video.command:
                    increment: service.service-6play-users.<customer>.command.<command>.<value>
                m6video.krux.segments.error:
                    increment: service.service-6play-users.krux.segments.error.<platformCode>.<value>
                m6video.sms.send:
                    increment: service.service-6play-users.sms.<provider>.<type>.<success>.<code>
                m6video.krux.call:
                    timing:    service.service-6play-users.krux.call.<route>.<platformCode>.<value>
                    increment: service.service-6play-users.krux.call.<route>.<platformCode>.<value>
                m6video.gigya.notifications:
                    increment: service.service-6play-users.gigya.notifications.<value>
                m6video.store.request:
                    timing: service.service-6play-users.store.validation.<platformCode>.<storeCode>.<statusCode>
                m6video.store.validation.error:
                    increment: service.service-6play-users.store.validation.<platformCode>.<storeCode>.error.<value>
                m6video.store.validation.success:
                    increment: service.service-6play-users.store.validation.<platformCode>.<storeCode>.success
                m6video.store.freemium_pack.active:
                    increment: service.service-6play-users.store.freemium_pack.<platformCode>.<storeCode>.<active>
                m6video.import.status:
                    increment: service.service-6play-users.import.<keyspace>.<table>.<status>
                    immediate_send: true
                applaunch.client.error:
                    increment: applaunch.server.error.service-6play-users
                applaunch.client.success:
                    increment: applaunch.server.success.service-6play-users
                m6video.mindbaz.export:
                    increment: service.service-6play-users.mindbaz.export.<type>.<value>
                m6video.client.release:
                    increment: service.service-6play-users.client.release.<platformCode>.<release>
                m6video.user.delete:
                    increment: service.service-6play-users.user.delete.<value>

                m6web.cassandra:
                    timing: service.service-6play-users.cassandra.all_keyspaces.<command>

                redis.command:
                    increment: cache.redis.composant.<command>.service-6play-users
                    timing   : cache.redis.composant.<command>.service-6play-users

                daemon.loop.iteration:
                    increment:      worker.service-6play-users.<command>.iteration
                    timing:         worker.service-6play-users.<command>.iteration
                    custom_timing:  { node: memory.service-6play-users.worker.<command>.iteration, method: getMemory }
                daemon.stop:
                    immediate_send: true

                daemon.iteration.statsd.immediatesend:
                    immediate_send: true

```
New one : 
```yaml
m6web_statsd_prometheus:

    servers:
        default_server:
            address: 'udp://%statsd_prometheus.address%'
            port: '%statsd_prometheus.port%'

    tags:
        project: 'service_6play_users'

    # Clients definition
    clients:

        # Default client
        default_client:
            server: 'default_server'
            groups:

                # Default group
                default_group:
                    events:

                        uniqu_anonymous_id:
                            metrics:
                                -   type: 'increment'
                                    name: 'uniqanonymousid'
                                    tags:
                                        platform: ~

                        m6kernel.terminate:
                            metrics:                                
                                # increment:     request.service-6play-users.<status_code>.<route_name>
                                # /!\ Increment event here is useless:
                                # => A counter is already provided by prometheus with the other metric
                                -   type: 'timer'
                                    name: 'request'
                                    param_value: 'getTiming'
                                    tags:
                                        status: 'statusCode'
                                        route: 'routeName'

                                -   type: 'timer'
                                    name: 'memory'
                                    param_value: 'getMemory'
                                    tags:
                                        status: 'statusCode'
                                        route: 'routeName'

                        m6web.console.command:
                            metrics:
                                -   type: 'increment'
                                    name: 'command_run'
                                    tags:
                                        command: 'underscoredCommandName'

                        m6web.console.exception:
                            flush_metrics_queue: true
                            metrics:
                                -   type: 'increment'
                                    name: 'command_exception'
                                    tags:
                                        command: 'underscoredCommandName'

                        m6web.console.terminate:
                            flush_metrics_queue: true
                            metrics:
                                -   type: 'timer'
                                    name: 'command_exception'
                                    param_value: 'executionTimeHumanReadable'
                                    tags:
                                        command: 'underscoredCommandName'

                        m6kernel.exception:
                            metrics:
                                -   type: 'increment'
                                    name: 'errors'
                                    tags:
                                        - 'statusCode'

                        m6video.user_box.pairing:
                            metrics:
                                -   type: 'increment'
                                    name: 'user_box'
                                    tags:
                                        platform: 'platformCode'
                                        status: ~

                        m6video.auto_pairing:
                            metrics:
                                -   type: 'increment'
                                    name: 'auto_pairing'
                                    tags:
                                        network: ~
                                        status: ~

                        m6video.redis.list.size:
                            metrics:
                                -   type: 'timer'
                                    name: 'command_redis_list_size'
                                    param_value: 'getTiming'
                                    tags:
                                        customer: ~
                                        command: ~

                        m6video.command.clean_deleted_tests:
                            flush_metrics_queue: true
                            metrics:
                                -   type: 'increment'
                                    name: 'command_clean_deleted_tests'
                                    tags:
                                        value: ~

                        m6video.command.export_user_data.send.error:
                            metrics:
                                -   type: 'increment'
                                    name: 'command_export_user_data_send_error'
                                    tags:
                                        customer: ~

                        m6video.command.client.error:
                            metrics:
                                -   type: 'increment'
                                    name: 'command_send_error'
                                    tags:
                                        customer: ~
                                        command: ~
                                        value: ~

                        m6video.command.redis.error:
                            metrics:
                                -   type: 'increment'
                                    name: 'command_redis_error'
                                    tags:
                                        customer: ~
                                        command: ~

                        m6video.command.import_user_data.client.error:
                            metrics:
                                -   type: 'increment'
                                    name: 'command_import_user_data_error'
                                    tags:
                                        value: ~

                        m6video.command:
                            metrics:
                                -   type: 'increment'
                                    name: 'command'
                                    tags:
                                        customer: ~
                                        command: ~
                                        value: ~

                        m6video.krux.segments.error:
                            metrics:
                                -   type: 'increment'
                                    name: 'krux_segments_error'
                                    tags:
                                        platform: 'platformCode'
                                        value: ~

                        m6video.sms.send:
                            metrics:
                                -   type: 'increment'
                                    name: 'sms'
                                    tags:
                                        provider: ~
                                        type: ~
                                        success: ~
                                        code: ~

                        m6video.krux.call:
                            metrics:                            
                                # increment: krux.call.<route>.<platformCode>.<value>
                                # /!\ Increment event here is useless:
                                # => A counter is already provided by prometheus with the other metric
                                -   type: 'timer'
                                    name: 'krux_call'
                                    param_value: 'geTiming'
                                    tags:
                                        route: ~
                                        platform: 'platformCode'
                                        value: ~

                        m6video.gigya.notifications:
                            metrics:
                                -   type: 'increment'
                                    name: 'gigya_notifications'
                                    tags:
                                        value: ~

                        m6video.store.request:
                            metrics:
                                -   type: 'timer'
                                    name: 'store_validation'
                                    param_value: 'getTiming'
                                    tags:
                                        platform: 'platformCode'
                                        store: 'storeCode'
                                        status: 'statusCode'

                        m6video.store.validation.error:
                            metrics:
                                -   type: 'increment'
                                    name: 'store_validation_error'
                                    tags:
                                        platform: 'platformCode'
                                        store: 'storeCode'
                                        value: ~

                        m6video.store.validation.success:
                            metrics:
                                -   type: 'increment'
                                    name: 'store_validation_success'
                                    tags:
                                        plaftorm: 'platformCode'
                                        store: 'storeCode'

                        m6video.store.freemium_pack.active:
                            metrics:
                                -   type: 'increment'
                                    name: 'store_freemium_pack'
                                    tags:
                                        plaform: 'platformCode'
                                        store: 'storeCode'
                                        active: ~

                        m6video.import.status:
                            flush_metrics_queue: true
                            metrics:
                                -   type: 'increment'
                                    name: 'import'
                                    tags:
                                        key: 'keyspace'
                                        table: ~
                                        status: ~

                        applaunch.client.error:
                            metrics:
                                -   type: 'increment'
                                    name: 'applaunch_server_error'

                        applaunch.client.success:
                            metrics:
                                -   type: 'increment'
                                    name: 'applaunch_server_success'

                        m6video.mindbaz.export:
                            metrics:
                                -   type: 'increment'
                                    name: 'mindbaz_export'
                                    tags:
                                        type: ~
                                        value: ~

                        m6video.client.release:
                            metrics:
                                -   type: 'increment'
                                    name: 'client_release'
                                    tags:
                                        platform: 'platformCode'
                                        release: ~

                        m6video.user.delete:
                            metrics:
                                -   type: 'increment'
                                    name: 'user_delete'
                                    tags:
                                        value: ~

                        m6web.cassandra:
                            metrics:
                                -   type: 'timer'
                                    name: 'cassandra_all_keyspaces'
                                    param_value: 'getTiming'
                                    tags:
                                        command: ~

                        redis.command:
                            metrics:
                                # increment: cache.redis.composant.<command>.service-6play-users
                                # /!\ Increment event here is useless:
                                # => A counter is already provided by prometheus with the other metric                                
                                -   type: 'timer'
                                    name: 'cache_redis_composant'
                                    param_value: 'getTiming'
                                    tags:
                                        command: ~

                        daemon.loop.iteration:
                            metrics:                                
                                # increment: worker.service-6play-users.<command>.iteration
                                # /!\ Increment event here is useless:
                                # => A counter is already provided by prometheus with the other metric
                                -   type: 'timer'
                                    name: 'worker_iteration'
                                    param_value: 'getTiming'
                                    tags:
                                        command: ~

                                -   type: 'timer'
                                    name: 'memory_worker_iteration'
                                    param_value: 'getMemory'
                                    tags:
                                        command: ~

                        daemon.stop:
                            flush_metrics_queue: true

                        daemon.iteration.statsd.immediatesend:
                            flush_metrics_queue: true
```
[Go back](../README.md)