Upgrade from version 1 to 2
======

### tags differences

All tags can now be defined the same way, whether they are from the configuration, a group or from an event.

### tags definition

 * To resolve a tag from the `container` or the `request`, nothing changes.
 * To resolve a tag from a parameter (within a `MonitoringEventInterface`), you must now prefix it with `%=`.
 * To resolve a tag from a property (within an event), you must now prefix it with `->`.
 * A null value (`~`) will no longer try to access the property with `key`, but only the parameter.
 * You can now use static values from everywhere.