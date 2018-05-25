<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace izi\base;
use Phal;

/**
 * Application is the base class for all application classes.
 *
 * For more details and usage information on Application, see the [guide article on applications](guide:structure-applications).
 *
 * @property \izi\web\AssetManager $assetManager The asset manager application component. This property is
 * read-only.
 * @property \izi\rbac\ManagerInterface $authManager The auth manager application component. Null is returned
 * if auth manager is not configured. This property is read-only.
 * @property string $basePath The root directory of the application.
 * @property \izi\caching\CacheInterface $cache The cache application component. Null if the component is not
 * enabled. This property is read-only.
 * @property array $container Values given in terms of name-value pairs. This property is write-only.
 * @property \izi\db\Connection $db The database connection. This property is read-only.
 * @property \izi\web\ErrorHandler|\izi\console\ErrorHandler $errorHandler The error handler application
 * component. This property is read-only.
 * @property \izi\i18n\Formatter $formatter The formatter application component. This property is read-only.
 * @property \izi\i18n\I18N $i18n The internationalization application component. This property is read-only.
 * @property \izi\log\Dispatcher $log The log dispatcher application component. This property is read-only.
 * @property \izi\mail\MailerInterface $mailer The mailer application component. This property is read-only.
 * @property \izi\web\Request|\izi\console\Request $request The request component. This property is read-only.
 * @property \izi\web\Response|\izi\console\Response $response The response component. This property is
 * read-only.
 * @property string $runtimePath The directory that stores runtime files. Defaults to the "runtime"
 * subdirectory under [[basePath]].
 * @property \izi\base\Security $security The security application component. This property is read-only.
 * @property string $timeZone The time zone used by this application.
 * @property string $uniqueId The unique ID of the module. This property is read-only.
 * @property \izi\web\UrlManager $urlManager The URL manager for this application. This property is read-only.
 * @property string $vendorPath The directory that stores vendor files. Defaults to "vendor" directory under
 * [[basePath]].
 * @property View|\izi\web\View $view The view application component that is used to render various view
 * files. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class Application extends Module
{
    /**
     * @event Event an event raised before the application starts to handle a request.
     */
    const EVENT_BEFORE_REQUEST = 'beforeRequest';
    /**
     * @event Event an event raised after the application successfully handles a request (before the response is sent out).
     */
    const EVENT_AFTER_REQUEST = 'afterRequest';
    /**
     * Application state used by [[state]]: application just started.
     */
    const STATE_BEGIN = 0;
    /**
     * Application state used by [[state]]: application is initializing.
     */
    const STATE_INIT = 1;
    /**
     * Application state used by [[state]]: application is triggering [[EVENT_BEFORE_REQUEST]].
     */
    const STATE_BEFORE_REQUEST = 2;
    /**
     * Application state used by [[state]]: application is handling the request.
     */
    const STATE_HANDLING_REQUEST = 3;
    /**
     * Application state used by [[state]]: application is triggering [[EVENT_AFTER_REQUEST]]..
     */
    const STATE_AFTER_REQUEST = 4;
    /**
     * Application state used by [[state]]: application is about to send response.
     */
    const STATE_SENDING_RESPONSE = 5;
    /**
     * Application state used by [[state]]: application has ended.
     */
    const STATE_END = 6;
    
    /**
     * @var string the namespace that controller classes are located in.
     * This namespace will be used to load controller classes by prepending it to the controller class name.
     * The default namespace is `app\controllers`.
     *
     * Please refer to the [guide about class autoloading](guide:concept-autoloading.md) for more details.
     */
    public $controllerNamespace = 'app\\controllers';
    /**
     * @var string the application name.
     */
    public $name = 'My Application';
    /**
     * @var string the charset currently used for the application.
     */
    public $charset = 'UTF-8';
    /**
     * @var string the language that is meant to be used for end users. It is recommended that you
     * use [IETF language tags](http://en.wikipedia.org/wiki/IETF_language_tag). For example, `en` stands
     * for English, while `en-US` stands for English (United States).
     * @see sourceLanguage
     */
    public $language = 'en-US';
    /**
     * @var string the language that the application is written in. This mainly refers to
     * the language that the messages and view files are written in.
     * @see language
     */
    public $sourceLanguage = 'en-US';
    /**
     * @var Controller the currently active controller instance
     */
    public $controller;
    /**
     * @var string|bool the layout that should be applied for views in this application. Defaults to 'main'.
     * If this is false, layout will be disabled.
     */
    public $layout = 'main';
    /**
     * @var string the requested route
     */
    public $requestedRoute;
    /**
     * @var Action the requested Action. If null, it means the request cannot be resolved into an action.
     */
    public $requestedAction;
    /**
     * @var array the parameters supplied to the requested action.
     */
    public $requestedParams;
    /**
     * @var array list of installed Yii extensions. Each array element represents a single extension
     * with the following structure:
     *
     * ```php
     * [
     *     'name' => 'extension name',
     *     'version' => 'version number',
     *     'bootstrap' => 'BootstrapClassName',  // optional, may also be a configuration array
     *     'alias' => [
     *         '@alias1' => 'to/path1',
     *         '@alias2' => 'to/path2',
     *     ],
     * ]
     * ```
     *
     * The "bootstrap" class listed above will be instantiated during the application
     * [[bootstrap()|bootstrapping process]]. If the class implements [[BootstrapInterface]],
     * its [[BootstrapInterface::bootstrap()|bootstrap()]] method will be also be called.
     *
     * If not set explicitly in the application config, this property will be populated with the contents of
     * `@vendor/yiisoft/extensions.php`.
     */
    public $extensions;
    /**
     * @var array list of components that should be run during the application [[bootstrap()|bootstrapping process]].
     *
     * Each component may be specified in one of the following formats:
     *
     * - an application component ID as specified via [[components]].
     * - a module ID as specified via [[modules]].
     * - a class name.
     * - a configuration array.
     * - a Closure
     *
     * During the bootstrapping process, each component will be instantiated. If the component class
     * implements [[BootstrapInterface]], its [[BootstrapInterface::bootstrap()|bootstrap()]] method
     * will be also be called.
     */
    public $bootstrap = [];
    /**
     * @var int the current application state during a request handling life cycle.
     * This property is managed by the application. Do not modify this property.
     */
    public $state;
    /**
     * @var array list of loaded modules indexed by their class names.
     */
    public $loadedModules = [];
    
    public $db , $loader, $di, $application;
    public $defaultRoute = 'site', $defaultController = 'index', $defaultAction = 'index';
    public $requestUrl;
    public $settings , $router;
    public $homeUrl = '/';
    public $adminModule  = ['admin'];
    public $slug;
    public $url;
    /**
     * Constructor.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     * Note that the configuration must contain both [[id]] and [[basePath]].
     * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
     */
    public function __construct($config = [])
    {
        /**
         * Loader
         */
        
        $this->loader = new \Phalcon\Loader();
        
        /**
         * Dependency injection
         */
        
        $this->di = new \Phalcon\Di\FactoryDefault();
        
        $this->di->set('db', function() use ($config) {
            $db = $config['components']['db'];
            return new \izi\db\Mysql(array(
                "host"     => $db['host'],
                "username" => $db['username'],
                "password" => $db['password'],
                "dbname"   => $db['dbname'],
                "options" => array(
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $db['charset']
                )
            ));
        });
        
        $this->db = $this->di['db'];
        
        $this->di->setShared('session', function() {
            $session = new \Phalcon\Session\Adapter\Files();
            $session->start();
            return $session;
        });
        
        $this->di->set('flash', function() {
            return new \Phalcon\Flash\Session();
        });
        
        $db = $this->db;
        $this->di->set('router',$this->setRouter($this->di));
        
        /**
         * Application
         */
        $this->application = new \Phalcon\Mvc\Application($this->di);
        
        $this->application->registerModules($config['modules']);
        
        Phal::$app = $this;
       
        static::setInstance($this);
        
        $this->state = self::STATE_BEGIN;                
        
        $this->preInit($config);
        
        $this->registerErrorHandler($config);
    
        //Component::__construct($config);
    }
    
    
    private function getDbRouter($di){
        $server = $this->getServerInfo();
        foreach($server as $k=>$v){
            defined($k) or define($k,$v);
        }
        $this->requestUrl = URL_PATH != '' ? URL_PATH : '/';
        /**
         * Get domain pointer
         */
        $s = $this->getDomainInfo();
        
        $dma = false;
        if(!empty($s)){
            define ('SHOP_TIME_LEFT',countDownDayExpired($s['to_date']));
            define ('SHOP_TIME_LIFE',($s['to_date']));
            define ('SHOP_STATUS',($s['status']));
            define ('__SID__',(float)$s['sid']);
            define ('__SITE_NAME__',$s['code']);
            define ('__TEMPLETE_DOMAIN_STATUS__',$s['state']);
            $defaultModule = $s['module'] != "" ? $s['module'] : $this->defaultRoute;
            
            /*
             *
             * */
            
            $pos = strpos(URL_PATH, '/sajax');
            if($pos === false){
                $this->defaultRoute = $defaultModule;
            }
            if($s['is_admin'] == 1){
                if($s['module'] == ""){
                    $this->defaultRoute = 'admin';
                }
                //
                
                $router = explode('/', trim(URL_PATH,'/'));
                
                if($router[0] != $this->defaultRoute && !in_array($router[0], $this->commonModule)){
                    $router = array_merge([$this->defaultRoute],$router);
                    $this->requesUrl = "/" . implode('/', $router);
                }
                
                
                //
                $dma = true;
            }
            
        }else{
            define ('SHOP_STATUS',0);
            define ('__SID__',0);
        }
        define('__DOMAIN_ADMIN__',$dma);
        define('__IS_SUSPENDED__',false);
        
        
         
        $this->settings = $this->db->getConfigs('SETTINGS',false,__SID__,false);
        
        if(!isset($this->settings['currency']['default'])){
            $this->c->setDefaultCurrency(1);
            $this->settings = $this->db->getConfigs('SETTINGS',false,__SID__,false);
        }
        //
        $suffix = isset($this->settings['url_manager']['suffix']) ? $this->settings['url_manager']['suffix']: '';
        define('URL_SUFFIX', $suffix);
        //$this->setUrlSuffix($suffix);
        
        define('ADMIN_ADDRESS',__DOMAIN_ADMIN__ ? $this->homeUrl : $this->homeUrl .  $this->adminModule[0]);
        
        define('ABSOLUTE_ADMIN_ADDRESS',__DOMAIN_ADMIN__ ? ABSOLUTE_DOMAIN : ABSOLUTE_DOMAIN . '/' .  $this->adminModule[0]);
        
        
        
        
        // customize
        $pos = strpos($this->requestUrl, '?');
        $this->router = trim($pos !== false ? substr($this->requestUrl, 0, $pos) : $this->requestUrl,'/');
        if(in_array($this->router, ['sitemap.xml','robots.txt'])){
            $this->router = str_replace(['.txt','.xml'], '', $this->router);
        }
        
        if(URL_SUFFIX != ""){
            $pos = stripos($this->router,URL_SUFFIX);
            if($pos !== false){
                $this->router = substr($this->router, 0, $pos);
            }
        }
        
        $router = explode("/",$this->router);
        
        if(in_array($router[0], $this->getAllModules())){
            defined('__IS_ADMIN__') or define('__IS_ADMIN__',in_array($router[0], $this->adminModule));
            defined('__IS_MODULE__') or define('__IS_MODULE__',true);
            defined('MODULE_ADDRESS') or define('MODULE_ADDRESS',__DOMAIN_ADMIN__ ? $this->homeUrl : $this->homeUrl . $router[0]);
            $this->defaultRoute = $router[0];
            unset($router[0]);
            $router = array_values($router);
        }else{
            defined('__IS_ADMIN__') or define('__IS_ADMIN__',false);
            defined('__IS_MODULE__') or define('__IS_MODULE__',false);
            defined('MODULE_ADDRESS') or define('MODULE_ADDRESS',$this->homeUrl);
        }
        //$filename = Phal::getAlias('@app/components/module_functions/' . $this->defaultRoute . '.php');
        //if(file_exists($filename)){
        //    require_once $filename;
        //}
        
        $url = $this->getDetailUrl($router);
        
        
        
        
        
        
        
    }
    
    public function getDetailUrl($router){
        $url = '';
        switch ($this->defaultRoute){
            case 'site':
                
                if(!in_array($router[0], ['tag','tags'])){
                    foreach ($r = array_reverse($router) as $url){
                        //$s = \izi\models\Slug::findUrl($url);
                        $sqlQuery = "SELECT * FROM slugs WHERE url='$url' and sid=".__SID__;
                        $s = $this->db->fetchOne($sqlQuery);
                        
                        if(!empty($s)){
                            $this->slug = $s;
                            $this->defaultController = $s['route'];
                            $this->url = $url;
                            break;
                        }
                    }
                }
                 
                
                break;
            case 'admin':
                
                defined('ADMIN_VERSION') or define('ADMIN_VERSION', $this->getAdminVersionCode()) ;
                
                foreach ($r = $router as $url){
                    $this->slug = \izi\models\Slug::adminFindByUrl($url);
                    break;
                }
                if(!empty($this->slug)){
                    $this->slug['hasChild'] = \izi\models\Slug::checkExistedChild($this->slug['id']);
                    if($this->slug['hasChild']){
                        $this->slug['route'] = 'default';
                    }
                }
                break;
        }
        
        if(URL_SUFFIX != ""){
            $pos = stripos($url, URL_SUFFIX);
            if($pos !== false){
                $url = substr($url, 0, $pos);
            }
        }
        //view($url);
        return $url;
    }
    
    public function getAllModules(){
        return array_keys($this->modules);
    }
    
    private function getDomainInfo($domain = __DOMAIN__){
        $query = "SELECT " . implode(',', ['a.sid','b.status','b.code','a.is_admin','a.module','b.to_date','a.state']);
        $query .= " FROM domain_pointer a inner join shops b on a.sid=b.id WHERE a.domain='".__DOMAIN__."'";
        
        return $this->db->fetchOne($query );
    }
    
    private function setRouter($di){
        $router = new \Phalcon\Mvc\Router();
        
        /**
         * 
         */
        $dbRouter = $this->getDbRouter($di);
        
        /**
         * 
         */
        
        //view($this->id);
        
        $router->setDefaultModule($this->defaultRoute);
        $nameSpace = '\\' . ($this->defaultRoute) . '\\controllers'; 
        
        $router->add(
            "/{$this->url}",
            [
                "module"     => $this->defaultRoute,
                "controller" => $this->defaultController,
                "action"     => $this->defaultAction,
                //"params"     => 4,
            ]
            );
        $router->add(
            "/{$this->url}/",
            [
                "module"     => $this->defaultRoute,
                "controller" => $this->defaultController,
                "action"     => $this->defaultAction,
                //"params"     => 4,
            ]
            );
        $router->add(
            "/{$this->url}/:action",
            [
                "module"     => $this->defaultRoute,
                "controller" => $this->defaultController,
                "action"     => 1,
                //"params"     => 4,
            ]
            );
        $router->add(
            "/{$this->url}/:action/:params",
            [
                "module"     => $this->defaultRoute,
                "controller" => $this->defaultController,
                "action"     => 1,
                "params"     => 2,
            ]
            );	 	
        $router->handle();
        
        return $router;
    }
    
    
    
    public function getServerInfo(){
        $s = $_SERVER;
        $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
        $sp = strtolower($s['SERVER_PROTOCOL']);
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
        $port = $s['SERVER_PORT'];
        $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
        $host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : $s['SERVER_NAME'];
        $path = ($s['REQUEST_URI'] ? $s['REQUEST_URI'] : $_SERVER['HTTP_X_ORIGINAL_URL']);
        $url = $protocol . '://' . $host . $port . $path;
        $pattern = ['/index\.php\//','/index\.php/'];
        $replacement = ['',''];
        $url = preg_replace($pattern, $replacement, $url);
        $a = parse_url($url);
        return [
            'FULL_URL'=>$url,
            'URL_NO_PARAM'=> $a['scheme'].'://'.$a['host'].$port.$a['path'],
            'URL_WITH_PATH'=>$a['scheme'].'://'.$a['host'].$port.$a['path'],
            'URL_NOT_SCHEME'=>$a['host'].$port.$a['path'],
            'ABSOLUTE_DOMAIN'=>$a['scheme'].'://'.$a['host'],
            'SITE_ADDRESS'=>'/',
            'SCHEME'=>$a['scheme'],
            'DOMAIN'=>$a['host'],
            "__DOMAIN__"=>$a['host'],
            'DOMAIN_NOT_WWW'=>preg_replace('/www./i','',$a['host'],1),
            'URL_NON_WWW'=>preg_replace('/www./i','',$a['host'],1),
            'URL_PORT'=>$port,
            'URL_PATH'=>$a['path'],
            '__TIME__'=>time(),
            'DS' => '/',
            'ROOT_USER'=>'root',
            'ADMIN_USER'=>'admin',
            'DEV_USER'=>'dev',
            'DEMO_USER'=>'demo',
            'USER'=>'user'
        ];
    }
    
    
    public function getBrowser()
    {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $bname = $ub = 'Unknown';
        $platform = 'Unknown';
        $version= "";
        
        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        }
        elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        }
        elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }
        
        // Next get the name of the useragent yes seperately and for good reason
        if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
        {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        }
        elseif(preg_match('/Firefox/i',$u_agent))
        {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        }
        elseif(preg_match('/Chrome/i',$u_agent))
        {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        }
        elseif(preg_match('/Safari/i',$u_agent))
        {
            $bname = 'Apple Safari';
            $ub = "Safari";
        }
        elseif(preg_match('/Opera/i',$u_agent))
        {
            $bname = 'Opera';
            $ub = "Opera";
        }
        elseif(preg_match('/Netscape/i',$u_agent))
        {
            $bname = 'Netscape';
            $ub = "Netscape";
        }
        
        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) .
        ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }
        
        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
                $version= $matches['version'][0];
            }
            else {
                $version= isset($matches['version'][1]) ? $matches['version'][1] : '';
            }
        }
        else {
            $version= $matches['version'][0];
        }
        
        // check if we have a number
        if ($version==null || $version=="") {$version="?";}
        $version = str_replace('.', '_',  $version);
        $pos = strpos($version,'_');
        $v = $pos !== false ? substr($version,0, $pos) : $version;
        return array(
            //'userAgent' => $u_agent,
            'name'      => strtolower($bname),
            'short_name'=> strtolower($ub),
            'browser' => strtolower($ub),
            'full_version'   => $version,
            'version'   => $v,
            'platform'  => $platform, // window - linux - ios - android
            'platform_version'  => $platform ,// win10, win8 ...,
            'device_type' => '', // Desktop or Mobile
            'device_pointing_method' => '', // Touch or mouse
            
            
            
        );
    }
    
    
    
    /**
     * Pre-initializes the application.
     * This method is called at the beginning of the application constructor.
     * It initializes several important application properties.
     * If you override this method, please make sure you call the parent implementation.
     * @param array $config the application configuration
     * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
     */
    public function preInit(&$config)
    {
        if (!isset($config['id'])) {
            throw new InvalidConfigException('The "id" configuration for the Application is required.');
        }
        if (isset($config['basePath'])) {
            $this->setBasePath($config['basePath']);
            unset($config['basePath']);
        } else {
            throw new InvalidConfigException('The "basePath" configuration for the Application is required.');
        }
        
        if (isset($config['vendorPath'])) {
            $this->setVendorPath($config['vendorPath']);
            unset($config['vendorPath']);
        } else {
            // set "@vendor"
            $this->getVendorPath();
        }
        if (isset($config['runtimePath'])) {
            $this->setRuntimePath($config['runtimePath']);
            unset($config['runtimePath']);
        } else {
            // set "@runtime"
            $this->getRuntimePath();
        }
        
        if (isset($config['timeZone'])) {
            $this->setTimeZone($config['timeZone']);
            unset($config['timeZone']);
        } elseif (!ini_get('date.timezone')) {
            $this->setTimeZone('UTC');
        }
        
        if (isset($config['container'])) {
            $this->setContainer($config['container']);
            
            unset($config['container']);
        }
        
        // merge core components with custom components
        foreach ($this->coreComponents() as $id => $component) {
            if (!isset($config['components'][$id])) {
                $config['components'][$id] = $component;
            } elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
                $config['components'][$id]['class'] = $component['class'];
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        
        $this->state = self::STATE_INIT;
        $this->bootstrap();
    }
    
    /**
     * Initializes extensions and executes bootstrap components.
     * This method is called by [[init()]] after the application has been fully configured.
     * If you override this method, make sure you also call the parent implementation.
     */
    protected function bootstrap()
    {
        
        if ($this->extensions === null) {
            $file = Phal::getAlias('@vendor/yiisoft/extensions.php');
            $this->extensions = is_file($file) ? include $file : [];
        }
        foreach ($this->extensions as $extension) {
            if (!empty($extension['alias'])) {
                foreach ($extension['alias'] as $name => $path) {
                    Phal::setAlias($name, $path);
                }
            }
            if (isset($extension['bootstrap'])) {
                $component = Phal::createObject($extension['bootstrap']);
                if ($component instanceof BootstrapInterface) {
                    Phal::debug('Bootstrap with ' . get_class($component) . '::bootstrap()', __METHOD__);
                    $component->bootstrap($this);
                } else {
                    Phal::debug('Bootstrap with ' . get_class($component), __METHOD__);
                }
            }
        }
        
        foreach ($this->bootstrap as $mixed) {
            $component = null;
            if ($mixed instanceof \Closure) {
                Phal::debug('Bootstrap with Closure', __METHOD__);
                if (!$component = call_user_func($mixed, $this)) {
                    continue;
                }
            } elseif (is_string($mixed)) {
                if ($this->has($mixed)) {
                    $component = $this->get($mixed);
                } elseif ($this->hasModule($mixed)) {
                    $component = $this->getModule($mixed);
                } elseif (strpos($mixed, '\\') === false) {
                    throw new InvalidConfigException("Unknown bootstrapping component ID: $mixed");
                }
            }
            
            if (!isset($component)) {
                $component = Phal::createObject($mixed);
            }
            
            if ($component instanceof BootstrapInterface) {
                Phal::debug('Bootstrap with ' . get_class($component) . '::bootstrap()', __METHOD__);
                $component->bootstrap($this);
            } else {
                Phal::debug('Bootstrap with ' . get_class($component), __METHOD__);
            }
        }
    }
    
    /**
     * Registers the errorHandler component as a PHP error handler.
     * @param array $config application config
     */
    protected function registerErrorHandler(&$config)
    {
        if (defined('YII_ENABLE_ERROR_HANDLER') && YII_ENABLE_ERROR_HANDLER) {
            if (!isset($config['components']['errorHandler']['class'])) {
                echo "Error: no errorHandler component is configured.\n";
                exit(1);
            }
            $this->set('errorHandler', $config['components']['errorHandler']);
            unset($config['components']['errorHandler']);
            $this->getErrorHandler()->register();
        }
    }
    
    /**
     * Returns an ID that uniquely identifies this module among all modules within the current application.
     * Since this is an application instance, it will always return an empty string.
     * @return string the unique ID of the module.
     */
    public function getUniqueId()
    { 
        return '';
    }
    
    /**
     * Sets the root directory of the application and the @app alias.
     * This method can only be invoked at the beginning of the constructor.
     * @param string $path the root directory of the application.
     * @property string the root directory of the application.
     * @throws InvalidArgumentException if the directory does not exist.
     */
    public function setBasePath($path)
    {
        parent::setBasePath($path);
        Phal::setAlias('@app', $this->getBasePath());
    }
    
    /**
     * Runs the application.
     * This is the main entrance of an application.
     * @return int the exit status (0 means normal, non-zero values mean abnormal)
     */
    public function run()
    {
         
        try {
             $this->application->handle()->send();
        } catch (ExitException $e) {
            $this->end($e->statusCode, isset($response) ? $response : null);
            return $e->statusCode;
        }
    }
    
    /**
     * Handles the specified request.
     *
     * This method should return an instance of [[Response]] or its child class
     * which represents the handling result of the request.
     *
     * @param Request $request the request to be handled
     * @return Response the resulting response
     */
    abstract public function handleRequest($request);
    
    private $_runtimePath;
    
    /**
     * Returns the directory that stores runtime files.
     * @return string the directory that stores runtime files.
     * Defaults to the "runtime" subdirectory under [[basePath]].
     */
    public function getRuntimePath()
    {
        if ($this->_runtimePath === null) {
            $this->setRuntimePath($this->getBasePath() . DIRECTORY_SEPARATOR . 'runtime');
        }
        
        return $this->_runtimePath;
    }
    
    /**
     * Sets the directory that stores runtime files.
     * @param string $path the directory that stores runtime files.
     */
    public function setRuntimePath($path)
    {
        $this->_runtimePath = Phal::getAlias($path);
        Phal::setAlias('@runtime', $this->_runtimePath);
    }
    
    private $_vendorPath;
    
    /**
     * Returns the directory that stores vendor files.
     * @return string the directory that stores vendor files.
     * Defaults to "vendor" directory under [[basePath]].
     */
    public function getVendorPath()
    {
        if ($this->_vendorPath === null) {
            $this->setVendorPath($this->getBasePath() . DIRECTORY_SEPARATOR . 'vendor');
        }
        
        return $this->_vendorPath;
    }
    
    /**
     * Sets the directory that stores vendor files.
     * @param string $path the directory that stores vendor files.
     */
    public function setVendorPath($path)
    {
        $this->_vendorPath = Phal::getAlias($path);
        Phal::setAlias('@vendor', $this->_vendorPath);
        Phal::setAlias('@bower', $this->_vendorPath . DIRECTORY_SEPARATOR . 'bower');
        Phal::setAlias('@npm', $this->_vendorPath . DIRECTORY_SEPARATOR . 'npm');
    }
    
    /**
     * Returns the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_get().
     * If time zone is not configured in php.ini or application config,
     * it will be set to UTC by default.
     * @return string the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-get.php
     */
    public function getTimeZone()
    {
        return date_default_timezone_get();
    }
    
    /**
     * Sets the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_set().
     * Refer to the [php manual](http://www.php.net/manual/en/timezones.php) for available timezones.
     * @param string $value the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-set.php
     */
    public function setTimeZone($value)
    {
        date_default_timezone_set($value);
    }
    
    /**
     * Returns the database connection component.
     * @return \izi\db\Connection the database connection.
     */
    public function getDb()
    {
        return $this->get('db');
    }
    
    /**
     * Returns the log dispatcher component.
     * @return \izi\log\Dispatcher the log dispatcher application component.
     */
    public function getLog()
    {
        return $this->get('log');
    }
    
    /**
     * Returns the error handler component.
     * @return \izi\web\ErrorHandler|\izi\console\ErrorHandler the error handler application component.
     */
    public function getErrorHandler()
    {
        return $this->get('errorHandler');
    }
    
    /**
     * Returns the cache component.
     * @return \izi\caching\CacheInterface the cache application component. Null if the component is not enabled.
     */
    public function getCache()
    {
        return $this->get('cache', false);
    }
    
    /**
     * Returns the formatter component.
     * @return \izi\i18n\Formatter the formatter application component.
     */
    public function getFormatter()
    {
        return $this->get('formatter');
    }
    
    /**
     * Returns the request component.
     * @return \izi\web\Request|\izi\console\Request the request component.
     */
    public function getRequest()
    {
        return $this->get('request');
    }
    
    /**
     * Returns the response component.
     * @return \izi\web\Response|\izi\console\Response the response component.
     */
    public function getResponse()
    {
        return $this->get('response');
    }
    
    /**
     * Returns the view object.
     * @return View|\izi\web\View the view application component that is used to render various view files.
     */
    public function getView()
    {
        return $this->get('view');
    }
    
    /**
     * Returns the URL manager for this application.
     * @return \izi\web\UrlManager the URL manager for this application.
     */
    public function getUrlManager()
    {
        return $this->get('urlManager');
    }
    
    /**
     * Returns the internationalization (i18n) component.
     * @return \izi\i18n\I18N the internationalization application component.
     */
    public function getI18n()
    {
        return $this->get('i18n');
    }
    
    /**
     * Returns the mailer component.
     * @return \izi\mail\MailerInterface the mailer application component.
     */
    public function getMailer()
    {
        return $this->get('mailer');
    }
    
    /**
     * Returns the auth manager for this application.
     * @return \izi\rbac\ManagerInterface the auth manager application component.
     * Null is returned if auth manager is not configured.
     */
    public function getAuthManager()
    {
        return $this->get('authManager', false);
    }
    
    /**
     * Returns the asset manager.
     * @return \izi\web\AssetManager the asset manager application component.
     */
    public function getAssetManager()
    {
        return $this->get('assetManager');
    }
    
    /**
     * Returns the security component.
     * @return \izi\base\Security the security application component.
     */
    public function getSecurity()
    {
        return $this->get('security');
    }
    
    /**
     * Returns the configuration of core application components.
     * @see set()
     */
    public function coreComponents()
    {
        return [
            'log' => ['class' => 'izi\log\Dispatcher'],
            'view' => ['class' => 'izi\web\View'],
            'formatter' => ['class' => 'izi\i18n\Formatter'],
            'i18n' => ['class' => 'izi\i18n\I18N'],
            'mailer' => ['class' => 'izi\swiftmailer\Mailer'],
            'urlManager' => ['class' => 'izi\web\UrlManager'],
            'assetManager' => ['class' => 'izi\web\AssetManager'],
            'security' => ['class' => 'izi\base\Security'],
        ];
    }
    
    /**
     * Terminates the application.
     * This method replaces the `exit()` function by ensuring the application life cycle is completed
     * before terminating the application.
     * @param int $status the exit status (value 0 means normal exit while other values mean abnormal exit).
     * @param Response $response the response to be sent. If not set, the default application [[response]] component will be used.
     * @throws ExitException if the application is in testing mode
     */
    public function end($status = 0, $response = null)
    {
        if ($this->state === self::STATE_BEFORE_REQUEST || $this->state === self::STATE_HANDLING_REQUEST) {
            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);
        }
        
        if ($this->state !== self::STATE_SENDING_RESPONSE && $this->state !== self::STATE_END) {
            $this->state = self::STATE_END;
            $response = $response ?: $this->getResponse();
            $response->send();
        }
        
        if (YII_ENV_TEST) {
            throw new ExitException($status);
        }
        
        exit($status);
    }
    
    /**
     * Configures [[Phal::$container]] with the $config.
     *
     * @param array $config values given in terms of name-value pairs
     * @since 2.0.11
     */
    public function setContainer($config)
    {
        Phal::configure(Phal::$container, $config);
    }
}
