# Configuration

## 1. Configure the servers

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

> :information_source: For further help, have a look at the [Examples](examples.md) section.

## 2. Configure the clients

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

See [3. Configure the groups](#3-configure-the-groups).


> :information_source: For further help, have a look at the [Examples](examples.md) section.


## 3. Configure the groups

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
                        event1:
                            [...]
                        event2:
                            [...]
                ohter_group: #this is the root key / group name.
                    events:
                        event3:
                            [...]
                        event4:
                            [...]
```

### Group options

* `{root key}` = group name

You can name the group key in camelCase or snake_case.

* `events` : array

See [4. Configure the events](#4-configure-the-events).

> :information_source: For further help, have a look at the [Examples](examples.md) section.


## 4. Configure the events

### Description

An event set up an event name, and metrics to send when it is called.

```yaml
m6web_statsd_prometheus:
    clients:
        default_client:            
            server: 'default_server'
            groups:
                default_group:
                    events:
                        eventName1: #this is the root key / event name.
                            flush_metrics_queue: true #Optional
                            metrics: 
                                - [...metric 1...]
                                - [...metric 2...]
                        eventName2: #this is the root key / event name.
                            metrics: 
                                - [...metric 1...]
                                - [...metric 2...]
                groupB: #This is an example name for a group
                    events:
                        eventName3: #this is the root key / event name.
                            metrics: 
                                - [...metric 1...]
                                - [...metric 2...]
                        eventName4: #this is the root key / event name.
                            flush_metrics_queue: false #Optional (Default value)
                            metrics: 
                                - [...metric 1...]
                                - [...metric 2...]
```

### Event options

* `{root key}` = event name

You can name the event key in camelCase or snake_case.

* `flush_metrics_queue`: boolean \[optional. Default: false\]

If this option is set to true, when this event is called, the queued metrics will be sent
to the UDP server directly without waiting for the kernel terminate event. 

* `metrics`: array

See [5. Configure the metrics](#5-configure-the-metrics).

> :information_source: For further help, have a look at the [Examples](examples.md) section.

## 5. Configure the metrics

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
                        eventName1:
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

See [6. Configure the tags](#6-configure-the-tags)
 
> :information_source: For further help, have a look at the [Examples](examples.md) section.

 
## 6. Configure the tags

### Tags scopes

There are 3 types of tag that you can use: 
* __global tag__: it is sent with every event metric. Its value is set in the config file.
* __group tag__: it is sent with every event metric of the same group. Its value is set in the config file.
* __metric tag__: it is sent only for the current event metric. Its value is sent with the event.  

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
                        eventName1:
                            metrics:
                                -   type: 'counter'
                                    name: 'metricName'
                                    param_value: 'counterValue'
                                    tags: #Metric tags
                                        tag_1_event_1: ~ #value here corresponds to an optional property accessor
                                        tag_2_event_1: 'myPropertyAccessor'
                                            
                        eventName12:
                            metrics:
                                -   type: 'increment'
                                    name: 'metricName'
                                    tags: #Metric tags
                                        tag_1_event_2: ~                                                                  
```

### Tag option / structure

* `{key}` = tag name

The key corresponds to the tag name that will be sent to Prometheus.

* `{value}` = property accessor

You can define, as a value, a property acessor that will return the tag value.
This is used for legacy purposes, when you work with specific event classes.

If you don't need it, use the null value: `~`.

### Tag resolvers

2 services are injected into tag value resolution: 
- `container` = container
- `request` = current request. This is an alias for `container.get('request_stack').getCurrentRequest()`

To activate those resolvers, you need to add: `@=` at the beggining of you tag value:
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
tagName3: '@=request.get("X-Header") matches "/def.*ult/" ? "yes" : "no"'
```

Please, have a look at the documentation syntax to go further in your usage: [ExpressionLanguage syntax](https://symfony.com/doc/current/components/expression_language/syntax.html).

:warning: This works only for global and group configuration tags.

### Send tags with event metrics

When you set a tag in a metric, the bundle will look for an associated value to return.

If you use the new format, it will look for a parameter named after your tag.

A compatibility fallback will automatically look into your event object.
First, if you have defined a property accessor, it will get the tag value there.
Otherwise, it will check if there is a public accessor associated to your tag name. 

This works like the former placeholders. 

See [Usage documentation](usage.md) for further explanations. 


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
                        eventName1:
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
                         eventName1:
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

 
> :information_source: For further help, have a look at the [Examples](examples.md) section.

## 7. Compatibility and legacy behaviour

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
                        kernel.terminate:
                            metrics:
                                -   type: 'increment'
                                    #project name and dynamics values are removed here for the metric name
                                    #we also provide a better name according to the naming convention
                                    name: 'http_request_total' 
                                    tags: #dynamic values are set in tags
                                        host: 'request_host'
                                        status: 'response.statusCode'
                        kernel.exception:
                            metrics:
                                -   type: 'increment'                                                                    
                                    #we provide a better name according to the naming convention
                                    name: 'http_error_count' 
                                    tags:
                                        code: 'exception.code'
                        redis.command:
                            metrics:
                                -   type: 'increment'
                                    #we provide a better name according to the naming convention
                                    name: 'cache_redis_composant_total'
                                    tags:
                                        command: ~
                        m6web.guzzlehttp:
                            metrics:
                                -   type: 'timer'
                                    #we provide a better name according to the naming convention
                                    name: 'guzzlehttp_seconds'
                                    #this parameter will match with the public function set in the sent event
                                    param_value: 'getTiming' 
                                    tags:
                                        clientId: ~
                                                                        
                                -   type: 'increment'                                    
                                    #we provide a better name according to the naming convention
                                    name: 'guzzlehttp_total'  
                                    tags:
                                        clientId: ~
                                        status: 'response.statusCode'
```

> :information_source: For further help, have a look at the [Examples](examples.md) section.

[Go back](../README.md)