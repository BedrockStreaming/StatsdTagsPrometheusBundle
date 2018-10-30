# Usage

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


[Go back](../README.md)
