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
use InvalidArgumentException;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query\Builder;
use Phal;
class Slug extends Model
{
    
    public function getSources()
    {
        return "slugs";
    }
    public static function findUrl($url = ''){
        $sqlQuery = "SELECT * FROM slugs WHERE url='$url' and sid=".__SID__;
        return Phal::$app->di['db']->fetchOne($sqlQuery);
        //return static::find()->where(['url'=>$url,'sid'=>__SID__])->asArray()->one();
    }
    
    public static function getAll(){
        
    }
}