<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Redirection module without using of Apache .htaccess files
 *
 * @package      Redirection
 * @author       Viacheslav Radionov
 * @copyright    (c) 2010 Viacheslav Radionov <radionov@gmail.com>
 * @license      http://kohanaphp.com/license.html
 */
class Redirection {

    // Instances
    protected static $instance;

    /**
     * Singleton pattern
     *
     * @return Redirection
     */
    public static function instance()
    {
        if ( ! isset(Redirection::$instance))
        {
            // Load the configuration for this type
            $config = Kohana::$config->load('redirection');

            // Create a new session instance
            Redirection::$instance = new Redirection($config);
        }

        return Redirection::$instance;
    }

    protected $config;

    /**
     * Loads configuration options
     *
     * @return  void
     */
    public function __construct($config = array())
    {
        $this->config = $config;
    }

    /**
     * Check matches and redirect
     *
     * @return  void
     */
    public function run()
    {
        // Do not use in CLI
        if ($this->config && ! Kohana::$is_cli)
        {
            $token = Profiler::start('Redirection', 'run');

            // Dirty hacks for URL request detection…
            // Can't use Request::factory()->url(); before their initializaion

            $protocol = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? 'https://' : 'http://';
            $url = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $search = str_replace($protocol.$_SERVER['HTTP_HOST'].URL::site(), '/', $url);

            foreach ($this->config as $from => $to)
            {
                $from = preg_replace('@#(.*)@', '', $from);
                $from = str_replace("\n", "\r", $from);
                $from = str_replace('\\ ', '%20', $from);
                $from = str_replace("/", "\/", $from);

                if (preg_match("/$from/i", $search, $matches))
                {
                    // Generate redirection url and remove extra slashes
                    $redirect = ltrim(preg_replace("/$from/", $to, $search), '/');

                    // Log redirection
                    if (array_key_exists('kohana-dblog', Kohana::modules()))
                    {
                        DBLog::instance()->add('redirection', 'INFO', 'Redirect from :from to :to', array(
                            ':from' => $search,
                            ':to'   => '/'.$redirect,
                        ));
                    }

                    // Check if redirect to other site
                    $bits = explode('/', $redirect); 
                    if ($bits[0] == 'http:' || $bits[0] == 'https:')
                    {
                        // Be careful! Kohana cuts second slash in queries
                        $redirect = $bits[0].'//'.$bits[1];
                    }
                    else
                    {
                        $redirect = URL::site($redirect, TRUE);
                    }

                    header('Location: '.$redirect, TRUE, 301);
                    exit;
                }
            }

            Profiler::stop($token);
        }
    }
}