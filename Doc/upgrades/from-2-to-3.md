Upgrade from version 2 to 3
======

### Monitoring event
The MonitoringEvent class is gone.
There is now an AbstractMonitoringEvent where its parameters property being protected instead of private.

### Events names
Events names are gone. We now use specialized events.
In the configuration, you should replace the old event names with the events class names.
