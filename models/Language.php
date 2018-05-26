<?php
/**
 *
 * @link http://iziweb.vn
 * @copyright Copyright (c) 2016 iziWeb
 * @email zinzinx8@gmail.com
 *
 */
namespace izi\models;
use Phal;
class Language extends \Phalcon\Mvc\Model
{
    public static $key = 'LANGUAGE';
    
    public static function tableName(){
        return 'site_configs';
    }
    public static function getSources()
    {
        return self::tableName();
    }
    public static function tableLanguage(){
        return 'ad_languages';
    }
    
    public function initialize(){
         
    }
    /**
     * Begin
     **/
    
     
    /**
     * Lấy danh sách mặc định khi csdl chưa có
     *
     */
    public static function getDefaultLang(){
        return [
            array('id'=>1,'code'=>'vi_VN','hl_code'=>'vi','title'=>'Tiếng Việt','lang_code'=>'vietnamese','default'=>1,'is_active'=>1,'root_active'=>1),
            array('id'=>2,'code'=>'en_US','hl_code'=>'en-US','title'=>'Tiếng Anh','lang_code'=>'english-us','default'=>0,'is_active'=>1,'root_active'=>1),
            array('id'=>3,'code'=>'th_TH','hl_code'=>'th','title'=>'Tiếng Thái','lang_code'=>'thai','default'=>0,'is_active'=>0,'root_active'=>0),
            array('id'=>4,'code'=>'lo_LA','hl_code'=>'lo','title'=>'Tiếng Lào','lang_code'=>'lao','default'=>0,'is_active'=>0,'root_active'=>0),
            array('id'=>5,'code'=>'id_ID','hl_code'=>'id','title'=>'Tiếng Indonesia','lang_code'=>'indonesian','default'=>0,'is_active'=>0,'root_active'=>0)
        ];
    }
    
    /**
     * Lấy thông tin các trường của ngôn ngữ qua mã code
     * @param string $code
     * @return array|unknown
     */
    public static function getLanguage($code = DEFAULT_LANG){
        return self::getItemByCode($code);
    }
    
    public static function getItem($id=0,$o=[]){
        
        if(is_numeric($id) && $id>0){
            $l = self::getList();
            if(!empty($l)){
                foreach ($l as $k=>$v){
                    if(isset($v['id']) && $v['id'] == $id){
                        return $v;
                    }
                }
            }
        }else{
            return self::getItemByCode($id,$o);
        }
        return [];
    }
    
    public static function getItemByCode($code,$o=[]){
        if(is_numeric($code) && $code>0){
            return self::getItem($code,$o);
        }else{
            $l = self::getList();
            if(!empty($l)){
                foreach ($l as $k=>$v){
                    if(isset($v['code']) && $v['code'] == $code){
                        return $v;
                    }
                }
            }
        }
        return [];
    }
    
    /**
     * Lấy danh sách ngôn ngữ được cài đặt riêng cho user
     * @param array $o
     * @return string
     */
    public static function getUserLanguage($o = []){
        $r = self::getList();
        if(!empty($r)){
            foreach ($r as $k=>$v){
                if(isset($v['root_active']) && $v['root_active'] == 1 && isset($v['is_active']) && $v['is_active'] == 1){}else{unset($r[$k]);}
            }
        }
        return $r;
    }
    
    /**
     * Lấy danh sách mã code các ngôn ngữ mà trang đang sử dụng
     * @return array[
     * 	'vi_VN',
     * 	'en_US
     * ]
     */
    public static function getUserLanguageCode($field = 'code'){
        $language = self::getUserLanguage();
        $r = [];
        if(!empty($language)){
            foreach ($language as $v){
                if(isset($v[$field])) $r[] = $v[$field];
            }
        }
        return $r;
    }
    
    
    
    /**
     * Get language from DB
     */
    private static function dbGetItem($id){
        if($id > 0){
            $item = static::find()
            ->from(self::tableLanguage())
            ->where(['id'=>$id]);
            return $item->asArray()->one();
        }else{
            return self::dbGetItemByCode($id);
        }
    }
    
    private static function dbGetItemByCode($code){
        if(is_numeric($code) && $code > 0){
            return self::dbGetItem($id);
        }else{
            $item = static::find()
            ->from(self::tableLanguage())
            ->where(['code'=>$code]);
            return $item->asArray()->one();
        }
    }
    
    /**
     * Lấy danh sách ngôn ngữ
     */
    public static function getList($o = []){
        $r = Phal::$app->db->getConfigs(self::$key,false,__SID__,false);
        view($r,true);
        if(empty($r)){
            $lang = self::dbGetItemByCode(ROOT_LANG);
            $lang['root_active'] = 1;
            $lang['is_active'] = 1;
            $lang['is_default'] = $lang['default'] = 1;
            $lang['domain'] = '';
            Yii::$app->db->updateBizData([$lang],[
                'code' => self::$key,
                'sid' => __SID__,
            ]);
            $r[0] = $lang;
        }
        
        if(isset($o['is_active']) && !empty($r)){
            foreach ($r as $k=>$v){
                if(isset($v['is_active']) && $v['is_active'] == $o['is_active']){}else{unset($r[$k]);}
            }
        }
        
        if(!empty($r) && isset($o['translate']) && $o['translate']){
            foreach ($r as $k => $v){
                $r[$k]['title'] =
                Yii::$app->t->translate($v['lang_code'], __IS_ADMIN__ ? ADMIN_LANG : __LANG__)
                . ' - ' . $v['title'];
                ;
            }
        }
        
        return $r;
    }
    
    /**
     * Lấy ngôn ngữ mặc định của trang
     * @return array
     */
    public static function getUserDefaultLanguage(){
        $l = self::getList();
        if(!empty($l)){
            foreach ($l as $k=>$v){
                if(isset($v['default']) && $v['default'] == 1){
                    return $v;
                }
                if(isset($v['is_default']) && $v['is_default'] == 1){
                    return $v;
                }
            }
        }
        return [];
    }
    
    public static function setDefaultLanguage(){
        /**
         * Set language
         *
         */
        $config = Yii::$app->session->get('config');
        defined('ROOT_LANG') or define("ROOT_LANG",'vi_VN');
        defined('SYSTEM_LANG') or define("SYSTEM_LANG",ROOT_LANG);
        defined('ADMIN_LANG') or define("ADMIN_LANG",SYSTEM_LANG);
        if(defined('__URL_LANG__')){
            defined('__LANG__') or define("__LANG__",__URL_LANG__);
            defined('DEFAULT_LANG') or define("DEFAULT_LANG",__URL_LANG__);
        }else{
            if(!isset($config['language'])){
                $default_lang = self::getUserDefaultLanguage();
                if(empty($default_lang)){
                    $default_lang = ['code'=>'vi_VN','title'=>'Tiếng Việt','name'=>'Tiếng Việt','country_code'=>'vn','hl_code'=>'vi'];
                }
                $language = ['language'=>$default_lang,'default_language'=>$default_lang];
                $config = $language;
                Yii::$app->session->set('config', $config);
            }else{
                
            }
            defined('__LANG__') or define("__LANG__",(isset($config['language']['code']) ? $config['language']['code'] : 'vi_VN'));
            defined('DEFAULT_LANG') or define("DEFAULT_LANG", isset($config['default_language']['code']) ? $config['default_language']['code'] : SYSTEM_LANG);
            
        }
        return __LANG__;
    }
    
    
    
    
}