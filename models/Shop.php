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

class Shop extends Model
{
    
    public function getSources()
    {
        return "shops";
    }
    public function getSID(){
        $r = $this->modelsManager->createBuilder()
        
        ->from(['a'=> "domain_pointer"])
        ->innerJoin('shops','a.sid=b.id','b')
        ->where('a.domain=:domain:',
            ['domain'=>__DOMAIN__]
            )
            ->columns(['a.sid,b.code,a.is_admin,a.module,b.to_date'])
            ->getQuery()->getSingleResult()->toArray();
    }
}