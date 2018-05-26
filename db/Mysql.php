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
use Phalcon\Db\Adapter\Pdo;
class Mysql extends \Phalcon\Db\Adapter\Pdo\Mysql
{
    
    protected $tableQuoteCharacter = "`";
    protected $columnQuoteCharacter = '`';
    
    
    public $builder;
        
    public function initialize(){
        exit;
    }
    public function __construct($descriptor){
        $this->builder = new \izi\db\Query();
       parent::__construct($descriptor);
    }
    
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
        if(is_array($table)){
            $alias = (array_keys($table))[0];
            $table = str_replace(['{{%','}}'], ['`','`'], (array_values($table))[0]);
        }else{
            $table = str_replace(['{{%','}}'], ['`','`'], $table);
            $alias = 'a';
        }
        $sqlQuery = "SELECT a.* FROM $table `$alias`";
        if(is_array($condition) && !empty($condition)){
            $sqlQuery .= " WHERE 1";
            foreach ($condition as $k=>$v){
                if(is_array($v)){
                    $sqlQuery .= " AND $k in ('" . implode('\',\'',$v) . "')";
                }else{
                    $sqlQuery .= " AND $k=" . (is_numeric($v) ? $v : "'$v'");
                }                
            }
        }elseif(!is_array($condition) && $condition != ""){
            $sqlQuery .= " WHERE $condition";
        }
        //view($sqlQuery);
        $item = $this->fetchOne($sqlQuery);
        
        if(isset($item['bizrule']) && ($content = json_decode($item['bizrule'],1)) != NULL){
            $item += $content;
            unset($item['bizrule']);
        }
        return $item;
    }
    
 
    
    
    
    
    
    
    
    
    /**
     * Quotes a string value for use in a query.
     * Note that if the parameter is not a string, it will be returned without change.
     * @param string $str string to be quoted
     * @return string the properly quoted string
     * @see http://www.php.net/manual/en/function.PDO-quote.php
     */
    public function quoteValue($str)
    {
        if (!is_string($str)) {
            return $str;
        }
        
        //view(Phal::$app->db->quote());
        
        //view((new \PDO('mysql:host='.dString('WXBtZFdYQnRaR3h2WTJGc2FHOXpkQT09').';dbname=yii2','yii2','888888'))->quote($str));
        
        if (substr($str, 0,1) == "'" && substr($str, -1,1) == "'") {
            return $str;
        }
        
        // the driver doesn't support quote (e.g. oci)
        return "'" . addcslashes(str_replace("'", "''", $str), "\000\n\r\\\032") . "'";
    }
    
    /**
     * Quotes a table name for use in a query.
     * If the table name contains schema prefix, the prefix will also be properly quoted.
     * If the table name is already quoted or contains '(' or '{{',
     * then this method will do nothing.
     * @param string $name table name
     * @return string the properly quoted table name
     * @see quoteSimpleTableName()
     */
    public function quoteTableName($name)
    {
        if (strpos($name, '(') !== false || strpos($name, '{{') !== false) {
            return $name;
        }
        if (strpos($name, '.') === false) {
            return $this->quoteSimpleTableName($name);
        }
        $parts = explode('.', $name);
        foreach ($parts as $i => $part) {
            $parts[$i] = $this->quoteSimpleTableName($part);
        }
        
        return implode('.', $parts);
    }
    
    /**
     * Quotes a column name for use in a query.
     * If the column name contains prefix, the prefix will also be properly quoted.
     * If the column name is already quoted or contains '(', '[[' or '{{',
     * then this method will do nothing.
     * @param string $name column name
     * @return string the properly quoted column name
     * @see quoteSimpleColumnName()
     */
    public function quoteColumnName($name)
    {
        if (strpos($name, '(') !== false || strpos($name, '[[') !== false) {
            return $name;
        }
        if (($pos = strrpos($name, '.')) !== false) {
            $prefix = $this->quoteTableName(substr($name, 0, $pos)) . '.';
            $name = substr($name, $pos + 1);
        } else {
            $prefix = '';
        }
        if (strpos($name, '{{') !== false) {
            return $name;
        }
        
        return $prefix . $this->quoteSimpleColumnName($name);
    }
    
    /**
     * Quotes a simple table name for use in a query.
     * A simple table name should contain the table name only without any schema prefix.
     * If the table name is already quoted, this method will do nothing.
     * @param string $name table name
     * @return string the properly quoted table name
     */
    public function quoteSimpleTableName($name)
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $endingCharacter = $this->tableQuoteCharacter;
        } else {
            list($startingCharacter, $endingCharacter) = $this->tableQuoteCharacter;
        }
        return strpos($name, $startingCharacter) !== false ? $name : $startingCharacter . $name . $endingCharacter;
    }
    
    /**
     * Quotes a simple column name for use in a query.
     * A simple column name should contain the column name only without any prefix.
     * If the column name is already quoted or is the asterisk character '*', this method will do nothing.
     * @param string $name column name
     * @return string the properly quoted column name
     */
    public function quoteSimpleColumnName($name)
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $endingCharacter = $this->columnQuoteCharacter;
        } else {
            list($startingCharacter, $endingCharacter) = $this->columnQuoteCharacter;
        }
        return $name === '*' || strpos($name, $startingCharacter) !== false ? $name : $startingCharacter . $name . $endingCharacter;
    }
    
    
    /**
     * Unquotes a simple table name.
     * A simple table name should contain the table name only without any schema prefix.
     * If the table name is not quoted, this method will do nothing.
     * @param string $name table name.
     * @return string unquoted table name.
     * @since 2.0.14
     */
    public function unquoteSimpleTableName($name)
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $this->tableQuoteCharacter;
        } else {
            $startingCharacter = $this->tableQuoteCharacter[0];
        }
        return strpos($name, $startingCharacter) === false ? $name : substr($name, 1, -1);
    }
    
    /**
     * Unquotes a simple column name.
     * A simple column name should contain the column name only without any prefix.
     * If the column name is not quoted or is the asterisk character '*', this method will do nothing.
     * @param string $name column name.
     * @return string unquoted column name.
     * @since 2.0.14
     */
    public function unquoteSimpleColumnName($name)
    {
        if (is_string($this->columnQuoteCharacter)) {
            $startingCharacter = $this->columnQuoteCharacter;
        } else {
            $startingCharacter = $this->columnQuoteCharacter[0];
        }
        return strpos($name, $startingCharacter) === false ? $name : substr($name, 1, -1);
    }
    
    /**
     * Returns the actual name of a given table name.
     * This method will strip off curly brackets from the given table name
     * and replace the percentage character '%' with [[Connection::tablePrefix]].
     * @param string $name the table name to be converted
     * @return string the real name of the given table name
     */
    public function getRawTableName($name)
    {
        if (strpos($name, '{{') !== false) {
            $name = preg_replace('/\\{\\{(.*?)\\}\\}/', '\1', $name);
            
            return str_replace('%', $this->db->tablePrefix, $name);
        }
        
        return $name;
    }
    
    
    
    
    public function quoteSql($sql)
    {
        return preg_replace_callback(
            '/(\\{\\{(%?[\w\-\. ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/',
            function ($matches) {
                if (isset($matches[3])) {
                    return $this->quoteColumnName($matches[3]);
                }
                
                return str_replace('%', $this->tablePrefix, $this->quoteTableName($matches[2]));
            },
            $sql
            );
    }
    
    
    public function getSchema(){
        
    }
    
    public function getPdoTypetSchema(){
        
    }
    
    
    
    public function createCommand(){
        
    }
    
    
    
    
    
    
}