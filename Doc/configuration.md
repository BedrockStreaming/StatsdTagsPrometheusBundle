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

### Metrics naming convention

#### 1. Allowed characters and format

This is the metric name. You can name the metric with lower letters, numbers and underscores: __[a-z0-9\_]__.

You are recommended to name the metrics in snake case. 
The statsd_exporter will transform every of metric names in snake_case. 
It will be easier for you to find your metrics later if the names are the same. 

#### 2. Explicit naming

Use explicit names and suffix every metric with its unit.

:information_source: Have a look at the 
[Prometheus naming rules recommendation](https://prometheus.io/docs/practices/naming/)

#### 3. Unity

Metrics name must be __unique__. 
You cannot have the same metric name even if you have different types.

The duplicated metric will be __ignored__.
 
#### 4. Stop using dynamic values 

Placeholders (dynamic variables like __request.\<statusCode\>__) in metric names are now __deprecated__. 

You better use __tags__ instead (See [6. Configure the tags](#6-configure-the-tags)). 

[Go back](../README.md)


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
__counter, gauge, increment, decrement, timer__ 

* `name`: string

:warning: See :oncoming_police_car:
 [Metrics naming rules convention](#metrics-naming-convention)
  :oncoming_police_car:

* `param_value`: string

This option defines which tag will return the metric value.
This option is __required__ for gauge, counter, and timer types only.

* `tags` : array

See [6. Configure the tags](#6-configure-the-tags)
 
> :information_source: For further help, have a look at the [Examples](examples.md) section.

 
## 6. Configure the tags

### :warning: Modifying configuration requires statsd_exporter reboot

Once you've sent a metric, if you change its configuration, changes will be ignored. 

You will need to reboot the statsd_exporter server in order to take into account the new changes.


### :oncoming_police_car::oncoming_police_car: Tag naming rules and default configuration 

In order to have common metric names amongst all of our applications, we have defined some common tags names.
If you need to get one of this data, please use the following name for your tag.

#### Global configuration
 
* `project`: __\[required\]__ 
Your project name. *(service_6play_users_cloud, service_6play_middleware, ...)*

#### Group configuration

None yet.

#### Metric configuration

* `customer`: default value : *default*. Can be *all* for non-related customer applications.
* `client`:  (UserBundle) 
* `platform`: default value : *all* 
* :warning: __Waiting for validation__ `release`: Value matching with the header X-Client-Release: 
This will create TOO MUCH metrics 
* `service`: (???)
* HTTP Request
   * `route`: Symfony route name *(status, get_subscriptions, etc.)*
   * `status`: HTTP response code
* HTTP Request to an external service
   * `route`: Symfony route name *(status, get_subscriptions, etc.)*
   * `status`: HTTP response code
   * `project_to`: external service called
* Dynamo: (???) :warning: Work in progress
   * `table`
   * `result`

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
        project: 'service_6play_users_cloud'        
    
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
                                        tag_1_event_1: ~ # value here corresponds to an optionnal property accessor
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
        project: 'service_6play_users_cloud'        
    
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
                    increment: 'request.service-6play-broadcast.<request_host>.<response_statusCode>'
                kernel.exception:
                    increment: 'errors.<exception.code>.service-6play-broadcast.error'
                redis.command:
                    increment: 'cache.redis.composant.<command>.service-6play-broadcast'
                m6web.guzzlehttp:
                    timing: 'guzzlehttp.service-6play-broadcast.<clientId>'
                    increment: 'guzzlehttp.service-6play-broadcast.<clientId>.<response_statusCode>'
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
        project: 'service_6play_broadcast'         
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