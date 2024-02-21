<?php
    namespace Glowie\Plugins\Debugger;

    use Glowie\Core\Plugin;
    use Glowie\Core\View\Skeltch;
    use Glowie\Core\View\View;

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
         * List of console messages.
         * @var array
         */
        private static $messages = [];

        /**
         * Initializes the plugin.
         */
        public function register(){
            // Register directives
            Skeltch::directive('debugger', '<?php \Glowie\Plugins\Debugger\Debugger::render(); ?>');
        }

        /**
         * Renders the debug bar.
         */
        public static function render(){
            $view = new View(__DIR__ . '/resources/debugger.phtml', [
                'css_path' => __DIR__ . '/resources/style.css',
                'messages' => self::$messages
            ], true, true);

            echo $view->getContent();
        }

        /**
         * Prints an info message to the console.
         * @param string $message Message to print.
         */
        public static function info(string $message){
            self::addMessage('info', $message, 'green');
        }

        /**
         * Prints an error message to the console.
         * @param string $message Message to print.
         */
        public static function error(string $message){
            self::addMessage('error', $message, 'red');
        }

        /**
         * Prints a warning message to the console.
         * @param string $message Message to print.
         */
        public static function warning(string $message){
            self::addMessage('warning', $message, 'orange');
        }

        /**
         * Adds a message to the console list.
         * @param string $type Message type.
         * @param string $message Message text.
         * @param string $color Message color hex.
         */
        private static function addMessage(string $type, string $message, string $color){
            self::$messages[] = [
                'type' => $type,
                'date' => round((microtime(true) - APP_START_TIME) * 1000, 2) . 'ms',
                'text' => $message,
                'color' => $color
            ];
        }
    }

?>