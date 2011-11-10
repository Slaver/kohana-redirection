<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Redirection module without using of Apache .htaccess files
 * 
 * @todo 		404 error monitoring
 *
 * @package		Redirection
 * @author		Viacheslav Radionov
 * @copyright	(c) 2010 Viacheslav Radionov <radionov@gmail.com>
 * @license		http://kohanaphp.com/license.html
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
        $search = URL::site(Request::factory()->uri());

		if ($this->config)
        {
            foreach ($this->config as $from=>$to)
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
                    Kohana::$log->add(Log::INFO, 'Redirect from '.$search.' to '.$redirect);

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

                    Request::initial()->redirect($redirect, 301);
			    }
		    }
        }
	}
}