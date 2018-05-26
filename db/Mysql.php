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
use Phal;
class Mysql extends \Phalcon\Db\Adapter\Pdo\Mysql
{
        
    public function getConfigs($code = false, $lang = __LANG__,$sid=__SID__,$cached=true){
        $langx = $lang == false ? 'all' : $lang;
        $code = $code !== false ? $code : 'SITE_CONFIGS';
        $config = Phal::$app->session->get('config');
        if($cached && !isset($config['adLogin']) && isset($config['preload'][$code][$langx])
           && !empty($config['preload'][$code][$langx])){
               return $config['preload'][$code][$langx];
        }
        
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
                Phal::$app->session->set('config', $config);
                break;
        }
        return $l;
    }
    
    
    public function getItem($table, $condition){
        $table = str_replace(['{{%','}}'], ['`','`'], $table);
        $sqlQuery = "SELECT a.* FROM $table a";
        if(is_array($condition) && !empty($condition)){
            $sqlQuery .= " WHERE 1";
            foreach ($condition as $k=>$v){
                if(is_array($v)){
                    $sqlQuery .= " AND $k in (" . implode(',',$v) . ")";
                }else{
                    $sqlQuery .= " AND $k='$v'";
                }                
            }
        }elseif(!is_array($condition) && $condition != ""){
            $sqlQuery .= " WHERE $condition";
        }
        $item = $this->fetchOne($sqlQuery);
        if(isset($item['bizrule']) && ($content = json_decode($item['bizrule'],1)) != NULL){
            $item += $content;
            unset($item['bizrule']);
        }
        return $item;
    }
    
    
}