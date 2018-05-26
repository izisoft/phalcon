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
  
}