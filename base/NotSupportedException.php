<?php
/**
 *
 * @author pig
 * @link http://iziweb.vn
 * @copyright (c) 2018 iziweb
 * @email zinzinx8@gmail.com
 *
 */
namespace izi\base;

/**
 * NotSupportedException represents an exception caused by accessing features that are not supported.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class NotSupportedException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Not Supported';
    }
}
