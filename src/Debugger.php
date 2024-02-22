<?php
    namespace Glowie\Plugins\Debugger;

    use Glowie\Core\Http\Cookies;
    use Glowie\Core\Http\Rails;
    use Glowie\Core\Http\Session;
    use Glowie\Core\Plugin;
    use Glowie\Core\View\Skeltch;
    use Glowie\Core\View\View;
    use Util;
    use Env;
    use Throwable;
    use Closure;
    use Exception;

    /**
     * Glowie debug bar plugin.
     * @category Plugin
     * @package glowieframework/debugger
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class Debugger extends Plugin{

        /**
         * Checks if Debugger is already loaded.
         * @var bool
         */
        private static $isLoaded = false;

        /**
         * List of console messages.
         * @var array
         */
        private static $messages = [];

        /**
         * List of catched exceptions.
         * @var array
         */
        private static $exceptions = [];

        /**
         * List of timers.
         * @var array
         */
        private static $timers = [];

        /**
         * Initializes the plugin.
         */
        public function register(){
            Skeltch::directive('debugger', '<?php \Glowie\Plugins\Debugger\Debugger::render(); ?>');
        }

        /**
         * Renders the debug bar.
         */
        public static function render(){
            // Check if debug mode is enabled and if the debugger was not already loaded
            $debugMode = filter_var(Env::get('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
            if(self::$isLoaded || !$debugMode) return;

            // Order variables
            $request = Rails::getRequest()->toArray();
            ksort($request);

            $headers = Rails::getRequest()->getHeaders();
            ksort($headers);

            $response = Rails::getResponse()->getHeaders();
            ksort($response);

            // Inject view
            $view = new View(__DIR__ . '/resources/debugger.phtml', [
                'css_path' => __DIR__ . '/resources/style.min.css',
                'js_path' => __DIR__ . '/resources/script.min.js',
                'messages' => self::$messages,
                'exceptions' => self::$exceptions,
                'request' => $request,
                'status' => http_response_code(),
                'status_type' => self::parseStatusCode(http_response_code()),
                'headers' => $headers,
                'response' => $response,
                'session' => (new Session())->toArray(),
                'cookies' => (new Cookies())->toArray(),
                'route' => self::parseRouteInfo(),
                'timers' => self::parseTimers()
            ], true, true);

            // Return content
            self::$isLoaded = true;
            echo str_replace(["\n", "\r", "\t"], '', $view->getContent());
        }

        /**
         * Prints an info message to the console.
         * @param string $message Message to print.
         */
        public static function log(string $message){
            self::addMessage('info', $message);
        }

        /**
         * Prints an error message to the console.
         * @param string $message Message to print.
         */
        public static function error(string $message){
            self::addMessage('error', $message);
        }

        /**
         * Prints a warning message to the console.
         * @param string $message Message to print.
         */
        public static function warning(string $message){
            self::addMessage('warning', $message);
        }

        /**
         * Adds an exception to the log.
         * @param Throwable $exception Exception instance.
         */
        public static function exception(Throwable $exception){
            self::$exceptions[] = [
                'exception' => $exception,
                'time' => round((microtime(true) - APP_START_TIME) * 1000, 2) . 'ms'
            ];
        }

        /**
         * Clears the console and exceptions log.
         */
        public static function clear(){
            self::$messages = [];
            self::$exceptions = [];
        }

        /**
         * Starts a timer measurement.
         * @param string $name Timer name.
         */
        public static function startTimer(string $name){
            self::$timers[$name] = [
                'start' => microtime(true),
                'end' => null,
                'duration' => 0,
                'duration_string' => ''
            ];
        }

        /**
         * Stops a timer measurement.
         * @param string $name Timer name.
         */
        public static function stopTimer(string $name){
            if(empty(self::$timers[$name])) throw new Exception('stopTimer(): Timer with name "' . $name . '" was not started');

            $time = microtime(true);
            $duration = round(($time - self::$timers[$name]['start']) * 1000, 2);

            self::$timers[$name]['end'] = $time;
            self::$timers[$name]['duration'] = $duration;
            self::$timers[$name]['duration_string'] = ($duration <= 1000) ? ($duration . 'ms') : (round($duration / 1000, 2) . 's');
        }

        /**
         * Runs a function with measurement.
         * @param string $name Timer name.
         * @param Closure $callback Function to run.
         */
        public static function measure(string $name, Closure $callback){
            self::startTimer($name);
            call_user_func($callback);
            self::stopTimer($name);
        }

        /**
         * Adds a message to the console list.
         * @param string $type Message type.
         * @param string $message Message text.
         */
        private static function addMessage(string $type, string $message){
            self::$messages[] = [
                'type' => $type,
                'time' => round((microtime(true) - APP_START_TIME) * 1000, 2) . 'ms',
                'text' => $message,
            ];
        }

        /**
         * Parses the timers data.
         * @return array Returns the timers as an associative array.
         */
        private static function parseTimers(){
            $timers = Util::orderArray(self::$timers, 'duration', SORT_DESC);
            $renderTime = round((microtime(true) - APP_START_TIME) * 1000, 2);

            if(!empty($timers)){
                foreach($timers as $name => &$item){
                    if($item['end'] === null) self::stopTimer($name);

                    $size = ($item['duration'] / $renderTime) * 100;
                    $item['size'] = round($size, 2) . '%';
                }
            }

            return $timers;
        }

        /**
         * Parses the current route data.
         * @return array Route information as an associative array.
         */
        private static function parseRouteInfo(){
            $route = Rails::getRoute(Rails::getCurrentRoute());
            return [
                'name' => !Util::isEmpty($route['name']) ? $route['name'] : '/',
                'uri' => !empty($route['uri']) ? $route['uri'] : '/',
                'methods' => !empty($route['methods']) ? strtoupper(implode(', ', $route['methods'])) : 'ALL',
                'target' => !empty($route['controller']) ? ($route['controller'] . '::' . $route['action'] . '()') : ($route['code'] . ' ' . $route['redirect'])
            ];
        }

        /**
         * Returns the status code color.
         * @return string Color name as string.
         */
        private static function parseStatusCode(int $code){
            if($code >= 500) return 'yellow';
            if($code >= 400) return 'red';
            if($code >= 300) return 'blue';
            if($code >= 200) return 'green';
            return 'white';
        }
    }

?>