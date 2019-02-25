# Usage and examples

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Built-in events](#built-in-events)
  - [1. Kernel Events](#1-kernel-events)
  - [2. Console events](#2-console-events)
- [Dispatch your events](#dispatch-your-events)
  - [Using MonitoringEventInterface](#using-monitoringeventinterface)
  - [Other events and legacy behaviour](#other-events-and-legacy-behaviour)
- [Best practices](#best-practices)
  - [1. Use explicit default parameter name](#1-use-explicit-default-parameter-name)
  - [2. Add a global tag to set project name](#2-add-a-global-tag-to-set-project-name)
- [Examples](#examples)
  - [Example 1: Full configuration file example](#example-1-full-configuration-file-example)
  - [Example 2: Sending an increment metric](#example-2-sending-an-increment-metric)
  - [Example 3: Sending a counter, gauge or timing metric](#example-3-sending-a-counter-gauge-or-timing-metric)
  - [Example 4: Sending multiple metrics in one event](#example-4-sending-multiple-metrics-in-one-event)
  - [Example 5: Replacing placeholders (in the metric name)](#example-5-replacing-placeholders-in-the-metric-name)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->


## Built-in events

### 1. Kernel Events

#### `statsdprometheus.kernel.terminate`

This event decorates the `kernel.terminate` event and add useful monitoring metrics on top:
- host (http host requested by the browser)
- method (GET/POST/...)
- memory (maximum memory allocated)
- route (from symfony routing)
- status (200/404/5xx/...)
- timing (time elapsed since php started exection of the request)

#### `statsdprometheus.kernel.exception`

This event is provided for backward compatibility for your apps that used to use our `m6web/http-kernel-bundle` bundle. It provides the http response code sent.

### 2. Console events

#### `statsdprometheus.console.terminate`

This event decorates the `console.terminate` event and adds the following data:


- startTime: The command starting time
- executionTime: The execution time in microseconds
- executionTimeHumanReadable: Execution time, in seconds
- peakMemory: The peak memory usage
- underscoredCommandName: the formatted name of the current command
            
#### `statsdprometheus.console.command`

This sends the same values than `statsdprometheus.console.terminate`;

#### `statsdprometheus.console.error`

This sends the same values than `statsdprometheus.console.terminate`;

#### `statsdprometheus.console.exception`

This sends the same values than `statsdprometheus.console.terminate`;

## Dispatch your events

### Using MonitoringEventInterface

For every new event you need to dispatch, you have to use a class that implements 
`M6Web\Bundle\StatsdPrometheusBundle\Events\MonitoringEventInterface`.

This bundle offers you a generic implementation called
 `M6Web\Bundle\StatsdPrometheusBundle\Events\MonitoringEvent` that will handle most of your needs.
 So you won't need to create your own events anymore. You can use this class to send your events
  directly like this:
 
 ```php
 // Simple example without tags
 $eventDispatcher->dispatch('event1', new MonitoringEvent());
 
 // Simple example with tags
 $eventDispatcher->dispatch('event1', new MonitoringEvent([
     'myTagLabel1' => 'myTag_value1',
     'myTagLabel2' => 'myTag_value2',
 ]));
 
 // Example with tags and param value
  $eventDispatcher->dispatch('event1', new MonitoringEvent([
      'myTagLabel1' => 'myTag_value1',
      'myTagLabel2' => 'myTag_value2',
      // Defined param value
      'myCustomParamValue' => 'myValue',
  ]));
 ```

#### :warning: Warning

If you really need to create a specific event (for personal reasons), you can define your own class, 
but make sure that it will implement `MonitoringEventInterface`.

### Other events and legacy behaviour

If you need to use this bundle on an existing application, you are asked to perform a few changes 
in the configuration file of you application.

We have worked on compatibility with existing applications to prevent changes in any application code.

See [Configuration \> 7. Compatibility and legacy behaviour](configuration.md#7-compatibility-and-legacy-behaviour)

> For further help, have a look at the [Examples](examples.md) section.


## Best practices

```yaml
m6web_statsd_prometheus:

    servers:
        #Use explicit default naming
        default_server:
            address: "udp://localhost"
            port: 9125
          
    #Global tags  
    tags:         
        #Using global tags, we can inject the project name in every sent metrics
        project: 'my_project_name'        
    
    clients:
        #Use explicit default naming
        default_client:
            server: 'default_server'
            groups:
                default_group:
                    [...]
```

### 1. Use explicit default parameter name

Sometimes, you don't know how to name a parameter. So, you would like to use `default` as a name.
It can be confusing to se only "default" value everywhere. It would be better to use explicit naming,
 to understand what "default" stands for :
 * default_server 
 * default_client
 * default_group
 * ...


### 2. Add a global tag to set project name

Add a global tag named `project` to inject the project name in every metric that you will send.

Name your project in `snake_case`:
 * __my-project-name__ becomes __my_project_name__


## Examples


### Example 1: Full configuration file example

```yaml
m6web_statsd_prometheus:
    servers:
        default:
            address: 'udp://localhost'
            port: 1236
    tags:
        tagA: 'tagValue1' # static value
        tagB: 'tagValue2' # static value
    clients: #clients
        default:
            max_queued_metrics: 10000 #optional
            server: 'default'
            groups: #groups
                groupA:
                    tags:
                        tagC: 'tagValue3' #static value
                        tagD: 'tagValue4' #static value
                    events: #events types
                        eventName1:
                            metrics:
                                -   type: 'counter'
                                    name: 'metric_name'
                                    param_value: 'counterValue'
                                    tags:
                                        tagE: ~ # event's parameter "tagE"
                                        tagF: ~ # event's parameter "tagF"
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
                                -   type: 'gauge'
                                    name: 'metric_name4'
                                    param_value: 'myGauge'
                                    tags:
                                        tagE: ~ # event's parameter "tagE"
                        eventName5:
                            metrics:
                                -   type: 'timer'
                                    name: 'metric_name5'               
```

### Example 2: Sending an increment metric

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

### Example 3: Sending a counter, gauge or timing metric

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

### Example 4: Sending multiple metrics in one event

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

### Example 5: Replacing placeholders (in the metric name)

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

[Go back](../README.md)


[Go back](../README.md)
