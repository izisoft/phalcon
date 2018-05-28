<?php

/**
 *
 * @author pig
 * @link http://iziweb.vn
 * @copyright (c) 2018 iziweb
 * @email zinzinx8@gmail.com
 *
 */

namespace izi\db;

use Phal;

class Builder extends \Phalcon\Db\Adapter\Pdo\Mysql
{
    private static $instance = null;
    private $dbh = null, $table, $columns, $sql, $bindValues, $getSQL,
    $where, $orWhere, $whereCount=0, $isOrWhere = false,
    $rowCount=0, $limit, $orderBy, $lastIDInserted = 0;
    // Initial values for pagination array
    private $pagination = ['previousPage' => null,'currentPage' => 1,'nextPage' => null,'lastPage' => null, 'totalRows' => null];
    public function __construct()
    {
         
         
    }
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Builder();
        }
        return self::$instance;
    }
    
    public function a(){
        $this->sql .= ' a';
        return $this;
    }
    
    public function b(){
        $this->sql .= ' b';
        return $this;
    }
    
    public function c(){
        $this->sql .= ' c';
        return $this;
    }
    
    public function d(){
        return $this->sql;
    }
}