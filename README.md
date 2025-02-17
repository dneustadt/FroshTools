# Frosh Tools

This plugin contains some utility functions for managing a Shopware 6 shop.

The current feature set consists of:

- System Status
  - Checks PHP Version, MySQL, Queue is working etc.
- Cache manager
  - Lists App and Http Cache and all folders in var/cache
- Scheduled Task Manager
  - Shows all Scheduled Tasks and can execute one specific
- Queue Manager
  - Shows the amount of messages in the queue
- Log viewer
  - Shows the entries of /var/log/*.log files
- Task Logging
  - Can be enabled with env `FROSH_TOOLS_TASK_LOGGING=1` in `.env`. This will create a log in `var/log/task_logging-xx.log`

## Installation

- Clone this repository into custom/plugins of your Shopware 6 installation

## Commands

### `frosh:env:list` - Listing of all environment variables

`bin/console frosh:env:list`

`bin/console frosh:env:list --json`
Lists as json output

### `frosh:env:get` - Get environment variables

```bash
bin/console frosh:env:get APP_URL
http://localhost
```

```bash
bin/console frosh:env:get APP_URL --key-value
APP_URL=http://localhost
```

```bash
bin/console frosh:env:get APP_URL --json
{
    "APP_URL": "http:\/\/localhost"
}
```

### `frosh:env:set` - Set environment variables

```bash
bin/console frosh:env:set VARIABLE VALUE
```

### `frosh:env:del` - Delete environment variables

```bash
bin/console frosh:env:del VARIABLE
```

## Screenshots

![System Status](https://i.imgur.com/jZBzVFo.png)
![Cache Manager](https://i.imgur.com/JRpgbgl.png)
![Scheduled Task Manager](https://i.imgur.com/hWcHxuE.png)

