<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace izi\base;

use Phal;
use izi\di\ServiceLocator;

/**
 * Module is the base class for module and application classes.
 *
 * A module represents a sub-application which contains MVC elements by itself, such as
 * models, views, controllers, etc.
 *
 * A module may consist of [[modules|sub-modules]].
 *
 * [[components|Components]] may be registered with the module so that they are globally
 * accessible within the module.
 *
 * For more details and usage information on Module, see the [guide article on modules](guide:structure-modules).
 *
 * @property array $aliases List of path aliases to be defined. The array keys are alias names (must start
 * with `@`) and the array values are the corresponding paths or aliases. See [[setAliases()]] for an example.
 * This property is write-only.
 * @property string $basePath The root directory of the module.
 * @property string $controllerPath The directory that contains the controller classes. This property is
 * read-only.
 * @property string $layoutPath The root directory of layout files. Defaults to "[[viewPath]]/layouts".
 * @property array $modules The modules (indexed by their IDs).
 * @property string $uniqueId The unique ID of the module. This property is read-only.
 * @property string $version The version of this module. Note that the type of this property differs in getter
 * and setter. See [[getVersion()]] and [[setVersion()]] for details.
 * @property string $viewPath The root directory of view files. Defaults to "[[basePath]]/views".
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Module extends ServiceLocator
{
    /**
     * @event ActionEvent an event raised before executing a controller action.
     * You may set [[ActionEvent::isValid]] to be `false` to cancel the action execution.
     */
    const EVENT_BEFORE_ACTION = 'beforeAction';
    /**
     * @event ActionEvent an event raised after executing a controller action.
     */
    const EVENT_AFTER_ACTION = 'afterAction';

    /**
     * @var array custom module parameters (name => value).
     */
    public $params = [];
    /**
     * @var string an ID that uniquely identifies this module among other modules which have the same [[module|parent]].
     */
    public $id;
    /**
     * @var Module the parent module of this module. `null` if this module does not have a parent.
     */
    public $module;
    /**
     * @var string|bool the layout that should be applied for views within this module. This refers to a view name
     * relative to [[layoutPath]]. If this is not set, it means the layout value of the [[module|parent module]]
     * will be taken. If this is `false`, layout will be disabled within this module.
     */
    public $layout;
    /**
     * @var array mapping from controller ID to controller configurations.
     * Each name-value pair specifies the configuration of a single controller.
     * A controller configuration can be either a string or an array.
     * If the former, the string should be the fully qualified class name of the controller.
     * If the latter, the array must contain a `class` element which specifies
     * the controller's fully qualified class name, and the rest of the name-value pairs
     * in the array are used to initialize the corresponding controller properties. For example,
     *
     * ```php
     * [
     *   'account' => 'app\controllers\UserController',
     *   'article' => [
     *      'class' => 'app\controllers\PostController',
     *      'pageTitle' => 'something new',
     *   ],
     * ]
     * ```
     */
    public $controllerMap = [];
    /**
     * @var string the namespace that controller classes are in.
     * This namespace will be used to load controller classes by prepending it to the controller
     * class name.
     *
     * If not set, it will use the `controllers` sub-namespace under the namespace of this module.
     * For example, if the namespace of this module is `foo\bar`, then the default
     * controller namespace would be `foo\bar\controllers`.
     *
     * See also the [guide section on autoloading](guide:concept-autoloading) to learn more about
     * defining namespaces and how classes are loaded.
     */
    public $controllerNamespace;
    /**
     * @var string the default route of this module. Defaults to `default`.
     * The route may consist of child module ID, controller ID, and/or action ID.
     * For example, `help`, `post/create`, `admin/post/create`.
     * If action ID is not given, it will take the default value as specified in
     * [[Controller::defaultAction]].
     */
    public $defaultRoute = 'site';

    /**
     * @var string the root directory of the module.
     */
    private $_basePath;
    /**
     * @var string the root directory that contains view files for this module
     */
    private $_viewPath;
    /**
     * @var string the root directory that contains layout view files for this module.
     */
    private $_layoutPath;
    /**
     * @var array child modules of this module
     */
    private $_modules = [];
    /**
     * @var string|callable the version of this module.
     * Version can be specified as a PHP callback, which can accept module instance as an argument and should
     * return the actual version. For example:
     *
     * ```php
     * function (Module $module) {
     *     //return string|int
     * }
     * ```
     *
     * If not set, [[defaultVersion()]] will be used to determine actual value.
     *
     * @since 2.0.11
     */
    private $_version;

    private $_defaultRouter = 'site', $_defaultController = 'index', $_defaultAction = 'index';
    
    private $_router; // $_module
    
    private $_controller, $_action;

    /**
     * Constructor.
     * @param string $id the ID of this module.
     * @param Module $parent the parent module (if any).
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct($id, $parent = null, $config = [])
    {        
        $this->id = $id;
        $this->module = $parent;
        parent::__construct($config);
    }

    /**
     * Returns the currently requested instance of this module class.
     * If the module class is not currently requested, `null` will be returned.
     * This method is provided so that you access the module instance from anywhere within the module.
     * @return static|null the currently requested instance of this module class, or `null` if the module class is not requested.
     */
    public static function getInstance()
    {
        
        $class = get_called_class();
        return isset(Phal::$app->loadedModules[$class]) ? Phal::$app->loadedModules[$class] : null;
    }

    /**
     * Sets the currently requested instance of this module class.
     * @param Module|null $instance the currently requested instance of this module class.
     * If it is `null`, the instance of the calling class will be removed, if any.
     */
    public static function setInstance($instance)
    {
        if ($instance === null) {
            unset(Phal::$app->loadedModules[get_called_class()]);
        } else {
            Phal::$app->loadedModules[get_class($instance)] = $instance;
        }
    }

    /**
     * Initializes the module.
     *
     * This method is called after the module is created and initialized with property values
     * given in configuration. The default implementation will initialize [[controllerNamespace]]
     * if it is not set.
     *
     * If you override this method, please make sure you call the parent implementation.
     */
    public function init()
    {
        if ($this->controllerNamespace === null) {
            $class = get_class($this);
            if (($pos = strrpos($class, '\\')) !== false) {
                $this->controllerNamespace = substr($class, 0, $pos) . '\\controllers';
            }
        }
    }

    /**
     * Returns an ID that uniquely identifies this module among all modules within the current application.
     * Note that if the module is an application, an empty string will be returned.
     * @return string the unique ID of the module.
     */
    public function getUniqueId()
    {
        return $this->module ? ltrim($this->module->getUniqueId() . '/' . $this->id, '/') : $this->id;
    }

    /**
     * Returns the root directory of the module.
     * It defaults to the directory containing the module class file.
     * @return string the root directory of the module.
     */
    public function getBasePath()
    {
        if ($this->_basePath === null) {
            $class = new \ReflectionClass($this);
            $this->_basePath = dirname($class->getFileName());
        }

        return $this->_basePath;
    }

    /**
     * Sets the root directory of the module.
     * This method can only be invoked at the beginning of the constructor.
     * @param string $path the root directory of the module. This can be either a directory name or a [path alias](guide:concept-aliases).
     * @throws InvalidArgumentException if the directory does not exist.
     */
    public function setBasePath($path)
    {
        $path = Phal::getAlias($path);
        $p = strncmp($path, 'phar://', 7) === 0 ? $path : realpath($path);
        if ($p !== false && is_dir($p)) {
            $this->_basePath = $p;
        } else {
            throw new InvalidArgumentException("The directory does not exist: $path");
        }
    }

    /**
     * Returns the directory that contains the controller classes according to [[controllerNamespace]].
     * Note that in order for this method to return a value, you must define
     * an alias for the root namespace of [[controllerNamespace]].
     * @return string the directory that contains the controller classes.
     * @throws InvalidArgumentException if there is no alias defined for the root namespace of [[controllerNamespace]].
     */
    public function getControllerPath()
    {
        return Phal::getAlias('@' . str_replace('\\', '/', $this->controllerNamespace));
    }

    /**
     * Set & get default router
     */
    public function getDefaultRouter()
    {            
        return $this->_defaultRouter;
    }
    
    public function setDefaultRouter($router)
    {
        $this->_defaultRouter = $router;
    }
    
    
    /**
     * Set & get default controller
     */
    public function getDefaultController()
    {        
        return $this->_defaultController;
    }
    
    public function setDefaultController($controller)
    {
        $this->_defaultController = $controller;
    }
    
    /**
     * Set & get default action
     */
    public function getDefaultAction()
    {
        return $this->_defaultAction;
    }
    
    public function setDefaultAction($action)
    {
        $this->_defaultAction = $action;
    }
    
    /**
     * Set & get Router
     */
    public function getRouter()
    {
        if ($this->_router === null) {
            $this->_router = $this->_defaultRouter;
        }
        return $this->_router;
    }
    
    public function setRouter($router)
    {

        $this->_router = $router;
       
    }
    
    /**
     * Set & get Controller
     */
    public function getController()
    {
        if ($this->_controller === null) {
            $this->_controller = $this->_defaultController;
        }
        return $this->_controller;
    }
    
    public function setController($controller)
    {
        $this->_controller = $controller;
    }
    
    
    /**
     * Set & get Action
     */
    public function getAction()
    {
        if ($this->_action === null) {
            $this->_action = $this->_defaultAction;
        }
        return $this->_action;
    }
    
    public function setAction($action)
    {
        $this->_action = $action;
    }
    
    
    /**
     * Returns the directory that contains the view files for this module.
     * @return string the root directory of view files. Defaults to "[[basePath]]/views".
     */
    public function getViewPath()
    {        
        if ($this->_viewPath === null) {
            $this->_viewPath = dirname($this->getBasePath()) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $this->getRouter()  . DIRECTORY_SEPARATOR . 'views';
        }

        return $this->_viewPath;
    }

    /**
     * Sets the directory that contains the view files.
     * @param string $path the root directory of view files.
     * @throws InvalidArgumentException if the directory is invalid.
     */
    public function setViewPath($path)
    {
        $this->_viewPath = Phal::getAlias($path);
    }

    /*
     * set templete path
     */
    private $_templetePath;
    
    public function setTempletePath($path){
        $this->_templetePath = $path;
    }
    
    
    private $_templeteName ;
    
    public function getDbTempletePath(){
        
        /**
         * Get templete name from __SID__
         */
        $temp = \izi\models\Shop::getUserTemplete();
        
        
        switch (SHOP_STATUS){
            
            default:
                define('__TEMP_NAME__', __IS_MODULE__ ? $this->getRouter() : ($temp['name'] != "" ? $temp['name'] : 'coming1'));
                break;
        }
        $this->setTempleteName(__TEMP_NAME__);
        $config['TCID'][__SID__] = !empty($temp) ? $temp['parent_id'] : 0;
        $config['TID'][__SID__] = !empty($temp) ? $temp['id'] : 0;
        define('__TID__', $config['TID'][__SID__]);
        define('__TCID__', $config['TCID'][__SID__]);
        
        
        if($this->getDevice() != 'desktop' && $temp['is_mobile'] == 1){
            define('__IS_MOBILE_TEMPLETE__' , true );
            define('__MOBILE_TEMPLETE__' , '/' . $this->_device  );
        }else {
            define('__IS_MOBILE_TEMPLETE__', false);
            define('__MOBILE_TEMPLETE__' , '' );
        }
        
        $themePath = WEB_PATH . '/themes';
        $viewPath = $this->getViewPath();

        switch ($this->_device){
            case 'mobile':
                
                $dir = $themePath .DIRECTORY_SEPARATOR . __TEMP_NAME__ . __MOBILE_TEMPLETE__;
                $s = rtrim(Phal::$app->homeUrl,'/') . '/themes/'. __TEMP_NAME__ . __MOBILE_TEMPLETE__;
                
                if(!file_exists($dir)){
                    
                    $dir = $themePath .'/' . __TEMP_NAME__;
                    $s = rtrim($this->homeUrl,'/') . '/themes/'.__TEMP_NAME__.'';
                    $this->is_mobile = false;
                    $this->setViewPath($viewPath . DIRECTORY_SEPARATOR . __TEMP_NAME__ . __MOBILE_TEMPLETE__);
                }else{
                    $this->setViewPath($viewPath . DIRECTORY_SEPARATOR . __TEMP_NAME__);
                }
                define('__RSPATH__',$dir);
                define('__RSDIR__',__IS_MODULE__ ? rtrim(Phal::$app->homeUrl,'/') . '/themes/'.__TEMP_NAME__ : $s);
                break;
            default:
                
                $dir = $themePath .DIRECTORY_SEPARATOR . __TEMP_NAME__ . __MOBILE_TEMPLETE__;
                define('__RSPATH__',$dir);
                define('__RSDIR__',  rtrim(Phal::$app->homeUrl,'/'). '/themes/'.__TEMP_NAME__ . __MOBILE_TEMPLETE__);
                $this->setViewPath($viewPath . DIRECTORY_SEPARATOR . __TEMP_NAME__);
                break;
        }
        if(__IS_ADMIN__){
            define ('__VIEW_PATH__',__RSPATH__ . DIRECTORY_SEPARATOR . 'views');
        }else{
            
        }
         
        
        define ('__IS_MOBILE__',$this->is_mobile);
        define('__LIBS_DIR__',rtrim(Phal::$app->homeUrl,'/') . '/libs');
        define('__LIBS_PATH__',WEB_PATH . '/libs');
    }
    
    public function getTempletePath()
    {
        if ($this->_templetePath === null) {
            $this->_templetePath = $this->getViewPath();
            
            
            
            
        }
         
        return $this->_templetePath;
    }
    
    private $_device = 'desktop';
    public $is_mobile = false;
    public function getDevice($config=null){
        if($config == null){
            $config = Phal::$app->session->get('config');
        }
        // Get device
        if(isset($config['set_device']) && in_array($config['set_device'],['mobile','desktop'])){
            $this->_device=$config['device']=$config['set_device'];
            $t = false;
        }else{
            $t = true;
        }
        
        //
        if($t || !isset($config['device'])){
            $useragent=$_SERVER['HTTP_USER_AGENT'];
            
            if(preg_match('/(android|bb\d+|meego).+mobile|(android \d+)|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)
                ||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){
                    $this->_device = 'mobile';
                    $this->is_mobile = true;
            }
            $config['device'] = $this->_device;
        }else{
            $this->_device = $config['device'];
        }
        Phal::$app->session->set('config', $config);
        return $this->_device;
    }
    /**
     * Returns the directory that contains layout view files for this module.
     * @return string the root directory of layout files. Defaults to "[[viewPath]]/layouts".
     */
    public function getTempleteName()
    {
        if ($this->_templeteName === null) {
            $this->_templeteName = 'coming1';
        }

        return $this->_templeteName;
    }

    /**
     * Sets the directory that contains the layout files.
     * @param string $path the root directory or [path alias](guide:concept-aliases) of layout files.
     * @throws InvalidArgumentException if the directory is invalid
     */
    public function setTempleteName($name)
    {
        $this->_templeteName = $name;
    }
    
    
    /**
     * Returns the directory that contains layout view files for this module.
     * @return string the root directory of layout files. Defaults to "[[viewPath]]/layouts".
     */
    public function getLayoutPath()
    {
        if ($this->_layoutPath === null) {
            $this->_layoutPath = $this->getViewPath() . DIRECTORY_SEPARATOR . 'layouts';
        }
        
        return $this->_layoutPath;
    }
    
    /**
     * Sets the directory that contains the layout files.
     * @param string $path the root directory or [path alias](guide:concept-aliases) of layout files.
     * @throws InvalidArgumentException if the directory is invalid
     */
    public function setLayoutPath($path)
    {
        $this->_layoutPath = Phal::getAlias($path);
    }

    /**
     * Returns current module version.
     * If version is not explicitly set, [[defaultVersion()]] method will be used to determine its value.
     * @return string the version of this module.
     * @since 2.0.11
     */
    public function getVersion()
    {
        if ($this->_version === null) {
            $this->_version = $this->defaultVersion();
        } else {
            if (!is_scalar($this->_version)) {
                $this->_version = call_user_func($this->_version, $this);
            }
        }

        return $this->_version;
    }

    /**
     * Sets current module version.
     * @param string|callable $version the version of this module.
     * Version can be specified as a PHP callback, which can accept module instance as an argument and should
     * return the actual version. For example:
     *
     * ```php
     * function (Module $module) {
     *     //return string
     * }
     * ```
     *
     * @since 2.0.11
     */
    public function setVersion($version)
    {
        $this->_version = $version;
    }

    /**
     * Returns default module version.
     * Child class may override this method to provide more specific version detection.
     * @return string the version of this module.
     * @since 2.0.11
     */
    protected function defaultVersion()
    {
        if ($this->module === null) {
            return '1.0';
        }

        return $this->module->getVersion();
    }

    /**
     * Defines path aliases.
     * This method calls [[Phal::setAlias()]] to register the path aliases.
     * This method is provided so that you can define path aliases when configuring a module.
     * @property array list of path aliases to be defined. The array keys are alias names
     * (must start with `@`) and the array values are the corresponding paths or aliases.
     * See [[setAliases()]] for an example.
     * @param array $aliases list of path aliases to be defined. The array keys are alias names
     * (must start with `@`) and the array values are the corresponding paths or aliases.
     * For example,
     *
     * ```php
     * [
     *     '@models' => '@app/models', // an existing alias
     *     '@backend' => __DIR__ . '/../backend',  // a directory
     * ]
     * ```
     */
    public function setAliases($aliases)
    {
        foreach ($aliases as $name => $alias) {
            Phal::setAlias($name, $alias);
        }
    }

    /**
     * Checks whether the child module of the specified ID exists.
     * This method supports checking the existence of both child and grand child modules.
     * @param string $id module ID. For grand child modules, use ID path relative to this module (e.g. `admin/content`).
     * @return bool whether the named module exists. Both loaded and unloaded modules
     * are considered.
     */
    public function hasModule($id)
    {
        if (($pos = strpos($id, '/')) !== false) {
            // sub-module
            $module = $this->getModule(substr($id, 0, $pos));

            return $module === null ? false : $module->hasModule(substr($id, $pos + 1));
        }

        return isset($this->_modules[$id]);
    }

    /**
     * Retrieves the child module of the specified ID.
     * This method supports retrieving both child modules and grand child modules.
     * @param string $id module ID (case-sensitive). To retrieve grand child modules,
     * use ID path relative to this module (e.g. `admin/content`).
     * @param bool $load whether to load the module if it is not yet loaded.
     * @return Module|null the module instance, `null` if the module does not exist.
     * @see hasModule()
     */
    public function getModule($id, $load = true)
    {
        if (($pos = strpos($id, '/')) !== false) {
            // sub-module
            $module = $this->getModule(substr($id, 0, $pos));

            return $module === null ? null : $module->getModule(substr($id, $pos + 1), $load);
        }

        if (isset($this->_modules[$id])) {
            if ($this->_modules[$id] instanceof self) {
                return $this->_modules[$id];
            } elseif ($load) {
                Phal::debug("Loading module: $id", __METHOD__);
                /* @var $module Module */
                $module = Phal::createObject($this->_modules[$id], [$id, $this]);
                $module->setInstance($module);
                return $this->_modules[$id] = $module;
            }
        }

        return null;
    }

    /**
     * Adds a sub-module to this module.
     * @param string $id module ID.
     * @param Module|array|null $module the sub-module to be added to this module. This can
     * be one of the following:
     *
     * - a [[Module]] object
     * - a configuration array: when [[getModule()]] is called initially, the array
     *   will be used to instantiate the sub-module
     * - `null`: the named sub-module will be removed from this module
     */
    public function setModule($id, $module)
    {
        if ($module === null) {
            unset($this->_modules[$id]);
        } else {
            $this->_modules[$id] = $module;
        }
    }

    /**
     * Returns the sub-modules in this module.
     * @param bool $loadedOnly whether to return the loaded sub-modules only. If this is set `false`,
     * then all sub-modules registered in this module will be returned, whether they are loaded or not.
     * Loaded modules will be returned as objects, while unloaded modules as configuration arrays.
     * @return array the modules (indexed by their IDs).
     */
    public function getModules($loadedOnly = false)
    {
        if ($loadedOnly) {
            $modules = [];
            foreach ($this->_modules as $module) {
                if ($module instanceof self) {
                    $modules[] = $module;
                }
            }

            return $modules;
        }

        return $this->_modules;
    }

    /**
     * Registers sub-modules in the current module.
     *
     * Each sub-module should be specified as a name-value pair, where
     * name refers to the ID of the module and value the module or a configuration
     * array that can be used to create the module. In the latter case, [[Phal::createObject()]]
     * will be used to create the module.
     *
     * If a new sub-module has the same ID as an existing one, the existing one will be overwritten silently.
     *
     * The following is an example for registering two sub-modules:
     *
     * ```php
     * [
     *     'comment' => [
     *         'class' => 'app\modules\comment\CommentModule',
     *         'db' => 'db',
     *     ],
     *     'booking' => ['class' => 'app\modules\booking\BookingModule'],
     * ]
     * ```
     *
     * @param array $modules modules (id => module configuration or instances).
     */
    public function setModules($modules)
    {
        foreach ($modules as $id => $module) {
            $this->_modules[$id] = $module;
        }
    }

    /**
     * Runs a controller action specified by a route.
     * This method parses the specified route and creates the corresponding child module(s), controller and action
     * instances. It then calls [[Controller::runAction()]] to run the action with the given parameters.
     * If the route is empty, the method will use [[defaultRoute]].
     * @param string $route the route that specifies the action.
     * @param array $params the parameters to be passed to the action
     * @return mixed the result of the action.
     * @throws InvalidRouteException if the requested route cannot be resolved into an action successfully.
     */
    public function runAction($route, $params = [])
    {
        $parts = $this->createController($route);
        if (is_array($parts)) {
            /* @var $controller Controller */
            list($controller, $actionID) = $parts;
            $oldController = Phal::$app->controller;
            Phal::$app->controller = $controller;
            $result = $controller->runAction($actionID, $params);
            if ($oldController !== null) {
                Phal::$app->controller = $oldController;
            }

            return $result;
        }

        $id = $this->getUniqueId();
        throw new InvalidRouteException('Unable to resolve the request "' . ($id === '' ? $route : $id . '/' . $route) . '".');
    }

    /**
     * Creates a controller instance based on the given route.
     *
     * The route should be relative to this module. The method implements the following algorithm
     * to resolve the given route:
     *
     * 1. If the route is empty, use [[defaultRoute]];
     * 2. If the first segment of the route is a valid module ID as declared in [[modules]],
     *    call the module's `createController()` with the rest part of the route;
     * 3. If the first segment of the route is found in [[controllerMap]], create a controller
     *    based on the corresponding configuration found in [[controllerMap]];
     * 4. The given route is in the format of `abc/def/xyz`. Try either `abc\DefController`
     *    or `abc\def\XyzController` class within the [[controllerNamespace|controller namespace]].
     *
     * If any of the above steps resolves into a controller, it is returned together with the rest
     * part of the route which will be treated as the action ID. Otherwise, `false` will be returned.
     *
     * @param string $route the route consisting of module, controller and action IDs.
     * @return array|bool If the controller is created successfully, it will be returned together
     * with the requested action ID. Otherwise `false` will be returned.
     * @throws InvalidConfigException if the controller class and its file do not match.
     */
    public function createController($route)
    {
        if ($route === '') {
            $route = $this->defaultRoute;
        }

        // double slashes or leading/ending slashes may cause substr problem
        $route = trim($route, '/');
        if (strpos($route, '//') !== false) {
            return false;
        }

        if (strpos($route, '/') !== false) {
            list($id, $route) = explode('/', $route, 2);
        } else {
            $id = $route;
            $route = '';
        }

        // module and controller map take precedence
        if (isset($this->controllerMap[$id])) {
            $controller = Phal::createObject($this->controllerMap[$id], [$id, $this]);
            return [$controller, $route];
        }
        $module = $this->getModule($id);
        if ($module !== null) {
            return $module->createController($route);
        }

        if (($pos = strrpos($route, '/')) !== false) {
            $id .= '/' . substr($route, 0, $pos);
            $route = substr($route, $pos + 1);
        }

        $controller = $this->createControllerByID($id);
        if ($controller === null && $route !== '') {
            $controller = $this->createControllerByID($id . '/' . $route);
            $route = '';
        }

        return $controller === null ? false : [$controller, $route];
    }

    /**
     * Creates a controller based on the given controller ID.
     *
     * The controller ID is relative to this module. The controller class
     * should be namespaced under [[controllerNamespace]].
     *
     * Note that this method does not check [[modules]] or [[controllerMap]].
     *
     * @param string $id the controller ID.
     * @return Controller|null the newly created controller instance, or `null` if the controller ID is invalid.
     * @throws InvalidConfigException if the controller class and its file name do not match.
     * This exception is only thrown when in debug mode.
     */
    public function createControllerByID($id)
    {
        $pos = strrpos($id, '/');
        if ($pos === false) {
            $prefix = '';
            $className = $id;
        } else {
            $prefix = substr($id, 0, $pos + 1);
            $className = substr($id, $pos + 1);
        }

        if ($this->isIncorrectClassNameOrPrefix($className, $prefix)) {
            return null;
        }

        $className = preg_replace_callback('%-([a-z0-9_])%i', function ($matches) {
                return ucfirst($matches[1]);
            }, ucfirst($className)) . 'Controller';
        $className = ltrim($this->controllerNamespace . '\\' . str_replace('/', '\\', $prefix) . $className, '\\');
        if (strpos($className, '-') !== false || !class_exists($className)) {
            return null;
        }

        if (is_subclass_of($className, 'yii\base\Controller')) {
            $controller = Phal::createObject($className, [$id, $this]);
            return get_class($controller) === $className ? $controller : null;
        } elseif (YII_DEBUG) {
            throw new InvalidConfigException('Controller class must extend from \\yii\\base\\Controller.');
        }

        return null;
    }

    /**
     * Checks if class name or prefix is incorrect
     *
     * @param string $className
     * @param string $prefix
     * @return bool
     */
    private function isIncorrectClassNameOrPrefix($className, $prefix)
    {
        if (!preg_match('%^[a-z][a-z0-9\\-_]*$%', $className)) {
            return true;
        }
        if ($prefix !== '' && !preg_match('%^[a-z0-9_/]+$%i', $prefix)) {
            return true;
        }

        return false;
    }

    /**
     * This method is invoked right before an action within this module is executed.
     *
     * The method will trigger the [[EVENT_BEFORE_ACTION]] event. The return value of the method
     * will determine whether the action should continue to run.
     *
     * In case the action should not run, the request should be handled inside of the `beforeAction` code
     * by either providing the necessary output or redirecting the request. Otherwise the response will be empty.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     if (!parent::beforeAction($action)) {
     *         return false;
     *     }
     *
     *     // your custom code here
     *
     *     return true; // or false to not run the action
     * }
     * ```
     *
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        $event = new ActionEvent($action);
        $this->trigger(self::EVENT_BEFORE_ACTION, $event);
        return $event->isValid;
    }

    /**
     * This method is invoked right after an action within this module is executed.
     *
     * The method will trigger the [[EVENT_AFTER_ACTION]] event. The return value of the method
     * will be used as the action return value.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function afterAction($action, $result)
     * {
     *     $result = parent::afterAction($action, $result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param Action $action the action just executed.
     * @param mixed $result the action return result.
     * @return mixed the processed action result.
     */
    public function afterAction($action, $result)
    {
        $event = new ActionEvent($action);
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_ACTION, $event);
        return $event->result;
    }

    /**
     * {@inheritdoc}
     *
     * Since version 2.0.13, if a component isn't defined in the module, it will be looked up in the parent module.
     * The parent module may be the application.
     */
    public function get($id, $throwException = true)
    {
        if (!isset($this->module)) {
            return parent::get($id, $throwException);
        }

        $component = parent::get($id, false);
        if ($component === null) {
            $component = $this->module->get($id, $throwException);
        }
        return $component;
    }

    /**
     * {@inheritdoc}
     *
     * Since version 2.0.13, if a component isn't defined in the module, it will be looked up in the parent module.
     * The parent module may be the application.
     */
    public function has($id, $checkInstance = false)
    {
        return parent::has($id, $checkInstance) || (isset($this->module) && $this->module->has($id, $checkInstance));
    }
}
