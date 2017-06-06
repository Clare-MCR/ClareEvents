<?php
/**
 * Created by PhpStorm.
 * User: rg12
 * Date: 18/01/2017
 * Time: 17:20
 */

namespace Rest\Classes;


/**
 * @property mixed name
 * @property mixed eventDate
 * @property mixed costMain
 * @property mixed costSecond
 * @property mixed costExtra
 * @property array rows
 * @property mixed category
 * @property bool noBill
 * @property mixed row
 */
class EmailTemplate
{
    var $variables = array();
    var $path_to_file= array();
    function __construct($path_to_file)
    {
        if(!file_exists($path_to_file))
        {
            trigger_error('Template File not found!',E_USER_ERROR);
            return;
        }
        $this->path_to_file = $path_to_file;
    }

    public function __set($key,$val)
    {
        $this->variables[$key] = $val;
    }


    /**
     * @return string
     */
    public function compile()
    {
        ob_start();

        extract($this->variables);
        include($this->path_to_file);

        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}