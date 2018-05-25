<?php
/**
*
 * @author iziweb  
 * @link http://iziweb.vn
 * @copyright (c) 2016 iziweb
 * @email zinzinx8@gmail.com
 *
 */
 
namespace izi\db;

class Mysql extends \Phalcon\Db\Adapter\Pdo\Mysql
{
    
    
    public function getConfigs($code = false, $lang = __LANG__,$sid=__SID__,$cached=true){
        $langx = $lang == false ? 'all' : $lang;
        $code = $code !== false ? $code : 'SITE_CONFIGS';
        //$config = Yii::$app->session->get('config');
        //if($cached && !isset($config['adLogin']) && isset($config['preload'][$code][$langx])
        //    && !empty($config['preload'][$code][$langx])){
        //        return $config['preload'][$code][$langx];
        //}
        //
        $query = "SELECT a.bizrule from site_configs a WHERE a.code = '$code'";
        //$query = (new Query())->select(['a.bizrule'])->from(['a'=>'{{%site_configs}}'])
        //->where(['a.code'=>$code]);
        if($sid>0){
            //$query->andWhere(['a.sid'=>$sid]);
            $query .= " and a.sid=$sid";
        }
        if($lang !== false){
            //$query->andWhere(['a.lang'=>$lang]);
            $query .= " and a.lang='$lang'";
        }
        $j = $this->fetchColumn($query);
        if($code == 'VERSION'){
            
        }
        $l = json_decode($j,true);
        switch ($code){
            case 'SITEMAP':break;
            
            default:
                //$config['preload'][$code][$langx] = $l;
                ///Yii::$app->session->set('config', $config);
                break;
        }
        return $l;
    }
    
}