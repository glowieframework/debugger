## Debugger for Glowie

Debugger is a plugin for [Glowie Framework](https://github.com/glowieframework/glowie) that provides a powerful and elegant debug bar for developers.

<img style="width: 100%; max-width: 600px;" src="https://i.imgur.com/mfcu69M.png" alt="Debugger">

## Features

- Clean and intuitive debug bar with zero configuration
- Log and filter messages, warnings, errors, and variables in a built-in console
- Capture and display exceptions with full stack traces
- Track long-running operations in a performance timeline
- Inspect Request and Response data, including headers
- View Session and Cookie information
- Log SQL queries with bindings and execution time
- Inspect rendered views, layouts, and their parameters
- Monitor application details such as routes, memory usage, software versions, request time, and more
- Automatic light and dark theme support

## Installation

Install Debugger in your Glowie project using Composer:

```shell
composer require glowieframework/debugger
```

Then add the Debugger class to the `plugins` array in `app/config/Config.php`:

```php
'plugins' => [
    // ... other plugins here
    \Glowie\Plugins\Debugger\Debugger::class,
],
```

Next, add the Skeltch directive at the bottom of your main layout or desired view, just before the closing `</body>` tag:

```html
<body>
    <!-- your page contents here -->
    { debugger }
</body>
```

If you are not using the Skeltch templating engine, you can render the debug bar using the default PHP call:

```php
<body>
    <!-- your page contents here -->
    <?php \Glowie\Plugins\Debugger\Debugger::render(); ?>
</body>
```

## Debug mode

**The debug bar should never be used in production.** To enable it, the `APP_DEBUG` value in your `.env` file must be set to true.

```env
APP_DEBUG=true
```

**Make sure to disable this in your production environment.**

### Enabling or disabling the debug bar at runtime

You can control the debug bar rendering dynamically:

```php
use Glowie\Plugins\Debugger\Debugger;

Debugger::enable();

Debugger::disable();
```

> **Note:** The `enable()` method will have no effect if debug mode is disabled in your environment configuration.

## Usage

### Console window

Send messages to the console using the following methods:

```php
use Glowie\Plugins\Debugger\Debugger;

Debugger::log('Hello world!'); // Logs an info message
// or
Debugger::info('Hello world!');

Debugger::error('Something went wrong...'); // Logs an error message

Debugger::warning('Remember to check the docs.'); // Logs a warning message

Debugger::dump($myVar); // Dumps a variable to the console
```

### Exceptions window

Capture exceptions and display them in the Exceptions tab:

```php
use Glowie\Plugins\Debugger\Debugger;

try {
    throw new Exception('Whoops!');
} catch (\Throwable $th) {
    Debugger::exception($th);
    throw $th; // re-throws the exception
}
```

### Timeline window

Measure execution time of operations:

```php
use Glowie\Plugins\Debugger\Debugger;

Debugger::startTimer('test', 'My timer description');
// long operation
Debugger::stopTimer('test');

// or
Debugger::measure('test', function(){
    // long operation
});
```

### Cross-request data

By default, Debugger data is stored only for the current request. Each new request clears the previous information.

To persist debug data across requests:

```php
use Glowie\Plugins\Debugger\Debugger;

Debugger::startCapture();
// your Debugger calls here
Debugger::stopCapture();

// or
Debugger::capture(function(){
    // your Debugger calls here
});
```

### Clearing information

You can clear specific sections of the debug bar:

```php
use Glowie\Plugins\Debugger\Debugger;

Debugger::clear(); // Clears the console

Debugger::clearExceptions(); // Clears exceptions

Debugger::clearTimers(); // Clears timers

Debugger::clearQueries(); // Clears SQL queries
```

## Credits

Debugger and Glowie are currently developed by [Gabriel Silva](https://gabrielsilva.dev.br).
