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

[Go back](../README.md)