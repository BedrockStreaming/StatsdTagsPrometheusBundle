Configuration
======

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Configure the servers](#configure-the-servers)
- [Configure the clients](#configure-the-clients)
- [Configure the groups](#configure-the-groups)
- [Configure the events](#configure-the-events)
- [Configure the metrics](#configure-the-metrics)
- [Configure the tags](#configure-the-tags)
- [Compatibility and legacy behaviour](#compatibility-and-legacy-behaviour)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->


Configure the servers
------

### Description

Add a `servers` option in the config file, and list all your UDP servers.

```yaml
m6web_statsd_prometheus:
    servers:
        default_server: #this is the root key / server name.
            address: 'udp://localhost'
            port: 1234
        server1: #this is the root key / server name.
            address: 'udp://localhost'
            port: 1235
        server2: #this is the root key / server name.
            address: 'udp://localhost'
            port: 1236   
```

### Server options

* `{root key}` = server name

You can name the server key in camelCase or snake_case.

* `address`: string

Address to the UDP server, containing the protocol.

* `port`: int

> :information_source: For further help, have a look at the [Examples](usage-and-examples.md) section.

Configure the clients
------

### Description

Now you need to set the clients and how they are linked to the defined servers.

You need to add the `clients` option and detail as follow:

```yaml
m6web_statsd_prometheus:
    servers:
        default_server:
            address: 'udp://localhost'
            port: 1234
        server1:
            address: 'udp://localhost'
            port: 1235
        server2:
            address: 'udp://localhost'
            port: 1236
    clients:
        default_client: #this is the root key / client name.
            max_queued_metrics: 10 #optional
            server: 'default_server'
        client1: #this is the root key / client name.
            server: 'server1'
        client2: #this is the root key / client name.
            max_queued_metrics: 10000 #optional
            server: 'server2'        
```

### Client options

* `{root key}` = client name

You can name the client key in camelCase or snake_case.

* `server`: string

Name of the server you want to use for that client.

* `max_queued_metrics`: int (OPTIONAL)

This is the limit of metrics we can queue before sending them to the UDP server.
There is no limit by default.

* `groups` : array

See  Configure the groups](#3-configure-the-groups).


> :information_source: For further help, have a look at the [Examples](usage-and-examples.md) section.


Configure the groups
------

### Description

With this new version, you have to define your events into groups.
This offers a better clarity when you have a lot of events.


```yaml
m6web_statsd_prometheus:
    clients:
        default_client: #This is the client name            
            server: 'default_server'
            groups:
                default_group: #this is the root key / group name.
                    events:
                        Project\Event\EventClass1:
                            [...]
                        Project\Event\EventClass2:
                            [...]
                ohter_group: #this is the root key / group name.
                    events:
                        Project\Event\EventClass3:
                            [...]
                        Project\Event\EventClass4:
                            [...]
```

### Group options

* `{root key}` = group name

You can name the group key in camelCase or snake_case.

* `events` : array

See  Configure the events](#4-configure-the-events).

> :information_source: For further help, have a look at the [Examples](usage-and-examples.md) section.


Configure the events
------

### Description

In events, you set up each events and their associated metrics to send when it is dispatched.

```yaml
m6web_statsd_prometheus:
    clients:
        default_client:            
            server: 'default_server'
            groups:
                default_group:
                    events:
                        Project\Event\EventClass1: #this is the root key / event name.
                            flush_metrics_queue: true #Optional
                            metrics: 
                                - [...metric 1...]
                                - [...metric 2...]
                        Project\Event\EventClass2: #this is the root key / event name.
                            metrics: 
                                - [...metric 1...]
                                - [...metric 2...]
                groupB: #This is an example name for a group
                    events:
                        Project\Event\EventClass3: #this is the root key / event name.
                            metrics: 
                                - [...metric 1...]
                                - [...metric 2...]
                        Project\Event\EventClass4: #this is the root key / event name.
                            flush_metrics_queue: false #Optional (Default value)
                            metrics: 
                                - [...metric 1...]
                                - [...metric 2...]
```

### Event options

* `{root key}` = the event class (or event name) that is being listened

* `flush_metrics_queue`: boolean \[optional. Default: false\]

If this option is set to true, when this event is called, the queued metrics will be sent
to the UDP server directly without waiting for the kernel terminate event. 

* `metrics`: array

See  Configure the metrics](#5-configure-the-metrics).

> :information_source: For further help, have a look at the [Examples](usage-and-examples.md) section.

Configure the metrics
------

### Global prefix

You can set a global prefix that will be prepend to every metrics created.
It is useful when sharing your prometheus storage with multiple organizations.

```yaml
m6web_statsd_prometheus:
    metrics: 
        prefix: 'myorganization_'
```

### Description

This is the main structure you need to use.

> Note: It is now natively possible to send multiple metrics with one event.

```yaml
m6web_statsd_prometheus:
    clients: 
        default_client:
            server: 'default_server'
            groups:
                default_group:
                    events:
                        Project\Event\EventClass1:
                            metrics:
                                #This is the first metric definition
                                -   type: 'counter'
                                    name: 'first_metric_name'
                                    param_value: 'metricValue'
                                #This is the second metric definition   
                                -   type: 'increment'
                                    name: 'second_metric_name_total'
                                    tags:
                                        additional_parameter: ~
```

### Metric options

* `type`: string

This is the metric type.
 
 Available values are: 
__counter, gauge, increment, timer__

Note: The *increment* type is an alias of the *counter* type. 
The bundle will automatically set its value to 1.  

* `name`: string

__Reminder__: The name of all your metrics will be prefixed by the value of `m6web_statsd_prometheus.metrics.prefix`.
By default none.

* `param_value`: string

This option defines which tag will return the metric value.
This option is __required__ for gauge, counter, and timer types only.

* `tags` : array

See  Configure the tags](#6-configure-the-tags)
 
> :information_source: For further help, have a look at the [Examples](usage-and-examples.md) section.

 
Configure the tags
------

### Tags scopes

There are 3 types of tag that you can use: 
* __global tag__: it is sent with every event metric.
* __group tag__: it is sent with every event metric of the same group.
* __metric tag__: it is sent only for the current event metric.  

Here is an example of how to define those 3 types:

```yaml
m6web_statsd_prometheus:
    tags: #Global tags
        exampleTag: 'example_tag_Value' #value is required here
        #Using global tags, we can inject the project name in every sent metrics
        project: 'my_project'        
        #The service container is injected to resolve configuration tags value
        dynamic_one: '@=container.get("my_service_id").getMyValue()' 
        #The current request is injected to resolve configuration tags value
        another_dynamic_one: '@=request.get("X-Custom-Header")' 
    
    clients:
        default_client:
            server: 'default_server'
            groups:
                default_group: 
                    tags: #Group tags
                        tag_1_group_default: 'tagValueB' #value is required here
                        tag_2_group_default: 'tagValueB'
                    events:
                        Project\Event\EventClass1:
                            metrics:
                                -   type: 'counter'
                                    name: 'metricName'
                                    param_value: 'counterValue'
                                    tags: #Metric tags
                                        tag_1_event_1: ~ #value here corresponds to an optional property accessor
                                        tag_2_event_1: 'myPropertyAccessor'
                                            
                        Project\Event\EventClass12:
                            metrics:
                                -   type: 'increment'
                                    name: 'metricName'
                                    tags: #Metric tags
                                        tag_1_event_2: ~                                                                  
```

### Tag option / structure

The key corresponds to the tag name that will be sent to Prometheus.

* `{key}` = tag name

The value can have different meanings.

* `{value}` = some value
   * Starting with `@=`, it will use a tag resolver, see [below](#tag-resolvers).
   * Starting with `->`, it will try to resolve the followed attribute, see [below](#tag-properties)
   * Starting with `%=`, it will try to get this parameter if the event implements the `MonitoringEventInterface`.
   * A static value can also be used.
   * A null value (or `~`) will try to resolve the `key` like it was a parameter (`%=key`).

### Tag resolvers

2 services are injected into tag value resolution: 
- `container` = container
- `request` = master request. This is either from the handled event (if it is a KernelEvent) or an alias for `container.get('request_stack').getMasterRequest()`

```yaml
tagName: '@=request.get("X-Header")'
```

Your tag value will be evaluated by the Symfony [ExpressionLanguage](https://symfony.com/doc/current/components/expression_language.html) component. 

You can use ternary operator to get different values according to the contaxt:
```yaml
#Ternary operator:
tagName1: '@=request.get("X-Header") ? request.get("X-Header") : "default"'
#Or, simplified ternary operator:
tagName2: '@=request.get("X-Header") ?: "default"'
```

Ten, you can use more complicated tests to check a value:
```yaml
#Logic operator
tagName3: '@=request.get("X-Header") &&  not(request.get("X-Header-secondary")) ? "yes" : "no"'
#Regex
tagName4: '@=request.get("X-Header") matches "/def.*ult/" ? "yes" : "no"'
```

Please, have a look at the documentation syntax to go further in your usage: [ExpressionLanguage syntax](https://symfony.com/doc/current/components/expression_language/syntax.html).

:warning: This works only for global and group configuration tags.

See [Usage documentation](usage-and-examples.md) for further explanations. 

### Tag properties

You can use the [Symfony property accessor](https://symfony.com/doc/current/components/property_access.html) to get values from your event.

```yaml
tagName: `->propertyName`
```

This will try to get the value of you event's public attribute `propertyName` or try to access it with a getter (`getPropertyName`).
See the Symfony documentation for more information.


### Tag priority and overriding

You can use the same tag name in the different scopes. The tag value is prioritized in this order:
1) metric
2) group
3) global

This allows you to override a global or a group configuration in a specific context.

Look at this example for further help:

```yaml
m6web_statsd_prometheus:
    tags:
        project: 'my_project'        
    
    clients:
        client1:            
            groups:
                group1:
                    events:
                        Project\Event\EventClass1:
                            metrics:
                                -   type: 'increment'
                                    name: 'metricName'
                                    tags: 
                                        # This tag will override the "project" tag
                                        # set in the global configuration for this current metric
                                        project: ~
                group2:
                     tags:
                         tag1GroupA: 'tagValueB'
                         # This tag will override the "project" tag
                         # set in the global configuration for this current group
                         project: "group project"
                     events:
                         Project\Event\EventClass1:
                             metrics:
                                 -   type: 'increment'
                                     name: 'metricName'
                                     tags: 
                                         # This tag will override the "tag1GroupA" tag
                                         # set in the group configuration for this current metric
                                         tag1GroupA: ~                                                                                     
```

### :rotating_light: Modifying configuration requires statsd_exporter reboot 

Once you've sent a metric, if you change its configuration, changes will be ignored. 

You will need to reboot the statsd_exporter server in order to take into account the new changes.

 
> :information_source: For further help, have a look at the [Examples](usage-and-examples.md) section.

Compatibility and legacy behaviour
------

### Modify your configuration format

If you want to use this bundle in a project that used the former StatsdBundle, you will need to perform
a few changes in your configuration file, to adapt it to this new format (See chapters 1 to 6).

Here is an old configuration sample:
```yaml
m6_statsd:
    servers:
        default_server:
            address: 'udp://127.0.0.1'
            port: 8125
    clients:
        default:
            server: 'default_server'
            events:
                kernel.terminate:
                    increment: 'request.<request_host>.<response_statusCode>'
                kernel.exception:
                    increment: 'errors.<exception.code>.error'
                redis.command:
                    increment: 'cache.redis.composant.<command>'
                m6web.guzzlehttp:
                    timing: 'guzzlehttp.<clientId>'
                    increment: 'guzzlehttp.<clientId>.<response_statusCode>'
``` 
This is how you need to change it:
```yaml
m6web_statsd_prometheus:
    servers:
        default_server:
            address: 'udp://127.0.0.1'
            port: 8125
    tags:
        #this will inject the project name in all metrics automatically. 
        #Use snake case.
        project: 'my_project'         
    clients:
        default_client:
            server: 'default_server'
            groups:
                default_group:
                    events:
                        statsdprometheus.kernel.terminate:
                            metrics:
                                -   type: 'increment'
                                    #project name and dynamics values are removed here for the metric name
                                    #we also provide a better name according to the naming convention
                                    name: 'http_request_total' 
                                    tags: #dynamic values are set in tags
                                        host: ~
                                        status: ~
                                -   type: 'timer'
                                    #project name and dynamics values are removed here for the metric name
                                    #we also provide a better name according to the naming convention
                                    name: 'http_request_input_seconds' 
                                    param_value: 'getTiming'
                                    tags: #dynamic values are set in tags
                                        route: ~
                                        host: ~
                                        status: ~
                        statsdprometheus.kernel.exception:
                            metrics:
                                -   type: 'increment'                                                                    
                                    #we provide a better name according to the naming convention
                                    name: 'http_error_count' 
                                    tags:
                                        status: ~
                        redis.command:
                            metrics:
                                -   type: 'increment'
                                    #we provide a better name according to the naming convention
                                    name: 'cache_redis_composant_total'
                                    tags:
                                        command: ~
                        !php/const:M6Web\Bundle\GuzzleHttpBundle\EventDispatcher\GuzzleHttpEvent::EVENT_NAME:
                            metrics:
                                -   type: 'timer'
                                    #we provide a better name according to the naming convention
                                    name: 'http_guzzle_request_output_seconds'
                                    #this parameter will match with the public function set in the sent event
                                    param_value: 'getTiming' 
                                    tags:
                                        clientId: '->clientId'
                                        status: '->response.statusCode'
```

> :information_source: For further help, have a look at the [Examples](usage-and-examples.md) section.

[Go back](../README.md)
