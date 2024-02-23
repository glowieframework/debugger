<?php
    namespace Glowie\Plugins\Debugger;

    use Glowie\Core\Http\Cookies;
    use Glowie\Core\Http\Rails;
    use Glowie\Core\Http\Session;
    use Glowie\Core\Plugin;
    use Glowie\Core\View\Skeltch;
    use Glowie\Core\View\View;
    use Glowie\Core\View\Buffer;
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
                'request_method' => Rails::getRequest()->getMethod(),
                'method_type' => self::parseMethod(Rails::getRequest()->getMethod()),
                'status' => Rails::getResponse()->getStatusCode(),
                'status_type' => self::parseStatusCode(Rails::getResponse()->getStatusCode()),
                'headers' => $headers,
                'response' => $response,
                'session' => (new Session())->toArray(),
                'cookies' => (new Cookies())->toArray(),
                'timers' => self::parseTimers(),
                'application' => self::parseAppInfo()
            ], true, true);

            // Return content
            self::$isLoaded = true;
            echo $view->getContent();
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
         * Dumps a variable to the console.
         * @param mixed $var Variable to dump.
         */
        public static function dump($var){
            Buffer::start();
            var_dump($var);
            $content = Buffer::get();
            self::addMessage('dump', $content);
        }

        /**
         * Adds an exception to the log.
         * @param Throwable $exception Exception instance.
         */
        public static function exception(Throwable $exception){
            self::$exceptions[] = [
                'exception' => $exception,
                'time' => self::parseTime(self::now())
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
            self::$timers[$name]['duration_string'] = self::parseTime($duration);
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
                'time' =>  self::parseTime(self::now()),
                'text' => $message,
            ];
        }

        /**
         * Parses the timers data.
         * @return array Returns the timers as an associative array.
         */
        private static function parseTimers(){
            // Order timers from longest to shortest
            $timers = Util::orderArray(self::$timers, 'duration', SORT_DESC);
            $renderTime = self::now();

            if(!empty($timers)){
                foreach($timers as $name => &$item){
                    // Stop timer if not yet
                    if($item['end'] === null) self::stopTimer($name);

                    // Calculate the progress bar size
                    $size = ($item['duration'] / $renderTime) * 100;
                    $item['size'] = round($size, 2) . '%';
                }
            }

            // Return result
            return $timers;
        }

        /**
         * Gets the current time from the app start in milisseconds.
         * @return float Returns the current request time.
         */
        private static function now(){
            return round((microtime(true) - APP_START_TIME) * 1000, 2);
        }

        /**
         * Parses the application data.
         * @return array App information as an associative array.
         */
        private static function parseAppInfo(){
            $route = Rails::getRoute(Rails::getCurrentRoute());
            return [
                'Route' => !Util::isEmpty($route['name']) ? $route['name'] : '/',
                'Controller' => $route['controller'],
                'Action' => $route['action'],
                'PHP Version' => phpversion(),
                'Glowie Version' => Util::getVersion(),
                'Memory Usage' => self::parseMemory(memory_get_usage()),
                'Peak Usage' => self::parseMemory(memory_get_peak_usage()),
                'Request Time' => self::parseTime(self::now())
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

        /**
         * Parse a time float to a readable string.
         * @param float $time Time value.
         * @return string Returns the parsed string.
         */
        private static function parseTime(float $time){
            if($time <= 1000) return $time . 'ms';
            if($time <= 60000) return round($time / 1000, 2) . 's';
            if($time <= 3600000) return round($time / 60000, 2) . 'min';
            return round($time / 3600000, 2) . 'h';
        }

        /**
         * Parse a size value to a readable string.
         * @param int $size Size in bytes.
         * @return string Returns the parsed string.
         */
        private static function parseMemory(int $size){
            if ($size >= 1073741824){
                $size = number_format($size / 1073741824, 2) . ' GB';
            }else if ($size >= 1048576){
                $size = number_format($size / 1048576, 2) . ' MB';
            }else if ($size >= 1024){
                $size = number_format($size / 1024, 2) . ' KB';
            }else if ($size > 1){
                $size = $size . ' bytes';
            }else if ($size == 1){
                $size = $size . ' byte';
            }else{
                $size = '0 bytes';
            }
            return $size;
        }

        /**
         * Returns the request method color.
         * @return string Color name as string.
         */
        private static function parseMethod(string $method){
            switch ($method) {
                case 'GET':
                    return 'blue';
                case 'PUT':
                    return 'yellow';
                case 'POST':
                case 'PATCH':
                    return 'green';
                case 'DELETE':
                    return 'red';
                case 'HEAD':
                case 'OPTIONS':
                    return 'purple';
                default:
                    return 'white';
            }
        }
    }

?>