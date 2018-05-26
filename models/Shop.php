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
class Shop extends \Phalcon\Mvc\Model
{
    /**
     * Configs
     * @return string
     */
    public function getSource(){
        return $this->tableName();
    }
    
    public static function tableName(){
        return 'shops';
    }
    
    public static function tableArticle(){
        return '{{%articles}}';
    }
    
    public static function tableTemplete(){
        return 'templetes';
    }
    
    public static function tableTempleteToShop(){
        return 'temp_to_shop';
    }
    
    public static function tableDomain(){
        return '{{%domain_pointer}}';
    }
    
    public static function getDomainInfo($domain = __DOMAIN__){
        $key = md5(session_id() . __DOMAIN__);
        $config = Phal::$app->session->get($key);
        
        if(!PHAL_DEBUG && !empty($config)){
            return $config;
        }else{
            $config = static::find()
            ->select(['a.sid','b.status','b.code','a.is_admin','a.module','b.to_date','a.state'])
            ->from(['a'=>'{{%domain_pointer}}'])
            ->innerJoin(['b'=>'{{%shops}}'],'a.sid=b.id')
            ->where(['a.domain'=>__DOMAIN__])->asArray()->one();
            Phal::$app->session->set($key, $config);
            return $config;
        }
    }
    
    /**
     * Get seo config
     * @return array
     */
    public function setSeoConfig(){
        $seo = Phal::$app->db->getConfigs('SEO');
        if(isset($seo['domain_type']) && $seo['domain_type'] == 'multiple'){
            $domains = isset($seo['domain']) && $seo['domain'] != '' ? explode(',', $seo['domain']) : [];
            $sd = [];
            if(!empty($domains)){
                foreach ($domains as $domain){
                    if($domain == DOMAIN){
                        if(isset($seo[$domain])){
                            $sd = $seo[$domain];
                            unset($seo[$domain]);
                        }
                    }else{
                        if(isset($seo[$domain])){
                            unset($seo[$domain]);
                        }
                    }
                }
            }
            $this->config['seo'] = array_merge($sd,$seo);
            unset($seo);
        }elseif (isset($seo['domain_type'])){
            $this->config['seo'] = $seo;unset($seo);
        }
        $www = isset($this->config['seo']['www']) ? $this->config['seo']['www'] : -1;
        if(!isset($this->config['seo']['amp'])) {
            $this->config['seo']['amp'] = [];
        }
        switch ($www){
            case 0:
                if(strpos(ABSOLUTE_DOMAIN, 'www.') !== false){
                    header('Location:' . SCHEME  . '://' . URL_NON_WWW . URL_PORT . URL_PATH ,301);
                    exit;
                }
                break;
            case 1:
                if(strpos(ABSOLUTE_DOMAIN, 'www.') === false){
                    header('Location:' . SCHEME  . '://www.' . URL_NON_WWW . URL_PORT . URL_PATH ,301);
                    exit;
                }
                break;
        }
        return $this->config['seo'];
    }
    
    public static function getArticleDetail($item_id){
        $item = static::find()->from(self::tableArticle())->where([
            'id'=>$item_id,'sid'=>__SID__
        ])->asArray()->one();
        if(isset($item['bizrule']) && ($content = json_decode($item['bizrule'],1)) != NULL){
            $item += $content;
            unset($item['bizrule']);
        }
        
        if(isset($item['content']) && ($content = json_decode($item['content'],1)) != NULL){
            $item += $content;
            unset($item['content']);
        }
        return $item;
    }
    
    public static function setViewedCount($item_id){
        $sesision_id = session_id();
        if(!(isset($_SESSION[$sesision_id]['last_viewed'][$item_id]['time']) && __TIME__-$_SESSION[$sesision_id]['last_viewed'][$item_id]['time'] < 300)){
            $_SESSION[$sesision_id]['last_viewed'][$item_id]['time'] = __TIME__;
            Phal::$app->db->createCommand("update articles set viewed=viewed+1 where id=".$item_id)->execute();
        }
    }
    
    public static function setTemplete(){
        $config = Phal::$app->session->get('config');
        $TEMP = self::getTempleteName();
        switch (SHOP_STATUS){
            
            default:
                define('__TEMP_NAME__', __IS_ADMIN__ ? 'admin' : ($TEMP['name'] != "" ? $TEMP['name'] : 'coming1'));
                break;
        }
        
        $config['TCID'][__SID__] = !empty($TEMP) ? $TEMP['parent_id'] : 0;
        $config['TID'][__SID__] = !empty($TEMP) ? $TEMP['id'] : 0;
        define('__TID__', $config['TID'][__SID__]);
        define('__TCID__', $config['TCID'][__SID__]);
        
        
        // Get device
        if(isset($config['set_device']) && in_array($config['set_device'],['mobile','desktop'])){
            self::$device=$config['device']=$config['set_device'];
            $t = false;
        }else{
            $t = true;
        }
        
        //
        if($t || !isset($config['device'])){
            $useragent=$_SERVER['HTTP_USER_AGENT'];
            
            if(preg_match('/(android|bb\d+|meego).+mobile|(android \d+)|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)
                ||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){
                    self::$device = 'mobile';
                    self::$is_mobile = true;
            }
            $config['device'] = self::$device;
        }else{
            self::$device = $config['device'];
        }
        
        
        Phal::$app->session->set('config', $config);
        if(self::$device != 'desktop' && $TEMP['is_mobile'] == 1){
            define('__IS_MOBILE_TEMPLETE__' , true );
            define('__MOBILE_TEMPLETE__' , '/' . self::$device  );
        }else {
            define('__IS_MOBILE_TEMPLETE__', false);
            define('__MOBILE_TEMPLETE__' , '' );
        }
        
        
        
        $app_path = Phal::getAlias('@app');
        $themePath = Phal::getAlias('@themes');
        
        switch (self::$device){
            case 'mobile':
                
                $dir = $themePath .'/' . __TEMP_NAME__ . __MOBILE_TEMPLETE__;
                $s = removeLastSlashes(\yii\helpers\Url::home()) . '/themes/'. __TEMP_NAME__ . __MOBILE_TEMPLETE__;
                
                if(!file_exists($dir)){
                    
                    $dir = $themePath .'/' . __TEMP_NAME__;
                    $s = removeLastSlashes(\yii\helpers\Url::home()) . '/themes/'.__TEMP_NAME__.'';
                    self::$is_mobile = false;
                }
                define('__RSPATH__',$dir);
                define('__RSDIR__',__IS_ADMIN__ ? \yii\helpers\Url::base() . '/themes/'.__TEMP_NAME__ : $s);
                break;
            default:
                
                $dir = $themePath .'/' . __TEMP_NAME__ . __MOBILE_TEMPLETE__;
                define('__RSPATH__',$dir);
                define('__RSDIR__',  \yii\helpers\Url::base() . '/themes/'.__TEMP_NAME__ . __MOBILE_TEMPLETE__);
                break;
        }
        if(__IS_ADMIN__){
            define ('__VIEW_PATH__',__RSPATH__ . DIRECTORY_SEPARATOR . 'views');
        }else{
            
        }
        define ('__IS_MOBILE__',self::$is_mobile);
        define('__LIBS_DIR__',Phal::getAlias('@libs'));
        define('__LIBS_PATH__',Phal::getAlias('@frontend/web/libs'));
        
    }
    
    public static function getUserTemplete($cached =  true){
        defined('__TEMPLETE_DOMAIN_STATUS__') or define('__TEMPLETE_DOMAIN_STATUS__', 1);
        $config = Phal::$app->session->get('config');
        $c = __SID__ .'_'. PRIVATE_TEMPLETE;
        
        if(!PHAL_DEBUG && isset($config['templete'][$c][__LANG__]['name']) && $config['templete'][$c][__LANG__]['name'] != ""){
            return $config['templete'][$c][__LANG__];
        }else{
            
            $r = [];
             
 
            if(PRIVATE_TEMPLETE>0){
//                 $r = static::find()
//                 ->select(['a.*'])
//                 ->from(['a'=>self::tableTemplete()])
//                 ->where(['a.id'=>PRIVATE_TEMPLETE])->asArray()->one();
                $r = Phal::$app->db->getItem(['a'=>self::tableTemplete()], ['a.id'=>PRIVATE_TEMPLETE]);
            }
            if(empty($r)){
                //
                $sqlQuery = (new \izi\db\Query())
                ->select(['a.*'])
                ->from(['a'=>self::tableTemplete()])
                ->innerJoin(['b'=>self::tableTempleteToShop()],'a.id=b.temp_id')
                ->where(['b.state'=>__TEMPLETE_DOMAIN_STATUS__,'b.sid'=>__SID__,'b.lang'=>__LANG__])
                ->createCommand()->getRawSql()
                ;
                
                /**
                 * Get default templete
                 */
                $r = Phal::$app->db->fetchOne($sqlQuery);
                
          
                
                if(empty($r)){
                    $sqlQuery = (new \izi\db\Query())
                    ->select(['a.*'])
                    ->from(['a'=>self::tableTemplete()])
                    ->innerJoin(['b'=>self::tableTempleteToShop()],'a.id=b.temp_id')
                    ->where(['b.state'=>__TEMPLETE_DOMAIN_STATUS__,'b.sid'=>__SID__])->createCommand()->getRawSql();
                    
                    $r = Phal::$app->db->fetchOne($sqlQuery);
                    
                }
                //
                if(empty($r) && __TEMPLETE_DOMAIN_STATUS__ > 1){
                    $sqlQuery = (new \izi\db\Query())
                    ->select(['a.*'])
                    ->from(['a'=>self::tableTemplete()])
                    ->innerJoin(['b'=>self::tableTempleteToShop()],'a.id=b.temp_id')
                    ->where(['b.state'=>1,'b.sid'=>__SID__,'b.lang'=>__LANG__])->createCommand()->getRawSql();
                    $r = Phal::$app->db->fetchOne($sqlQuery);
                    
                    if(empty($r)){
                        $sqlQuery = (new \izi\db\Query())
                        ->select(['a.*'])
                        ->from(['a'=>self::tableTemplete()])
                        ->innerJoin(['b'=>self::tableTempleteToShop()],'a.id=b.temp_id')
                        ->where(['b.state'=>1,'b.sid'=>__SID__])->createCommand()->getRawSql();
                        
                        $r = Phal::$app->db->fetchOne($sqlQuery);
                    }
                }
            }
            $config['templete'][$c][__LANG__] = $r;
            Phal::$app->session->set('config', $config);
            return $r;
        }
    }
}