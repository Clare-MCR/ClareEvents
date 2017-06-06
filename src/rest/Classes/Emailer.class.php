<?php
/**
 * Created by PhpStorm.
 * User: rg12
 * Date: 18/01/2017
 * Time: 17:20
 */

namespace Rest\Classes;

use Rest\Classes\EmailTemplate;

class Emailer
{
    var $recipients = array();
    var $EmailTemplate;
    var $EmailContents;

    public function __construct($to = false)
    {
        if($to !== false)
        {
            if(is_array($to))
            {
                foreach($to as $_to){ $this->recipients[$_to] = $_to; }
            }else
            {
                $this->recipients[$to] = $to; //1 Recip
            }
        }
    }

    function SetTemplate(EmailTemplate $EmailTemplate)
    {
        $this->EmailTemplate = $EmailTemplate;
    }

    function send()
    {
        $content = $this->EmailTemplate->compile();
        print($content);
        //your email send code.
    }
}