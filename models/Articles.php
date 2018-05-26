<?php
/**
*
 * @author iziweb  
 * @link http://iziweb.vn
 * @copyright (c) 2016 iziweb
 * @email zinzinx8@gmail.com
 *
 */
 
namespace izi\models;
use Phal;

class Articles extends \Phalcon\Mvc\Model
{
    
    public function getSource() {
        $this->tableName();
    }
    
    
    public static function tableName(){
        return 'articles';
    }
    
    public static function getArticleDetail($item_id){
//         $item = static::find()->from(self::tableArticle())->where([
//             'id'=>$item_id,'sid'=>__SID__
//         ])->asArray()->one();
        
        $sqlQuery = "SELECT * FROM " . self::tableName() . " WHERE id=$item_id and sid=" .__SID__;
        
        $item = Phal::$app->db->fetchOne($sqlQuery);
        
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
    
}