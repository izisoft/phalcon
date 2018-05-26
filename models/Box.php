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
class Box extends \Phalcon\Mvc\Model
{
    
    public function getSource(){
        return $this->tableName();
    }
    
    public static function tableName(){
        return 'box';
    }
    
    
    
    /**
     *
     */
    
    public function getAll(){
        $query = static::find()
        ->from(['a'=>$this->tableName()])
        ->where(['a.sid'=>__SID__])
        ->andWhere(['>','a.state',-2]);
        return $query->asArray()->all();
    }
    
    public static function getItem($id){
        
        return Phal::$app->db->getItem(self::tableName(),['id'=>$id]);
        
        /* $query = static::find()
        ->where(['sid'=>__SID__,'id'=>$id]);
        $item = $query->asArray()->one();
        
        
        if(isset($item['bizrule']) && ($content = json_decode($item['bizrule'],1)) != NULL){
            $item += $content;
            unset($item['bizrule']);
        }
        return $item; */
    }
    
    
}