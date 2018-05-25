<?php
/**
 *
 * @author pig
 * @link http://iziweb.vn
 * @copyright (c) 2018 iziweb
 * @email zinzinx8@gmail.com
 *
 */

require __DIR__ . '/BasePhal.php';

/**
 * Yii is a helper class serving common framework functionalities.
 *
 * It extends from [[\yii\BaseYii]] which provides the actual implementation.
 * By writing your own Yii class, you can customize some functionalities of [[\yii\BaseYii]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Phal extends \izi\BasePhal
{
}

spl_autoload_register(['Phal', 'autoload'], true, true);
Phal::$classMap = require __DIR__ . '/classes.php';
Phal::$container = new izi\di\Container();
