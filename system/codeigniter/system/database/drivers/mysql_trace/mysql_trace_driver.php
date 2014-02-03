<?php

if(!defined('BASEPATH'))
    exit('No direct script access allowed');


require_once BASEPATH . 'database/drivers/mysql/mysql_driver.php';
require_once APPPATH . 'third_party/trace/mod.trace.php';

class CI_DB_mysql_trace_driver extends CI_DB_mysql_driver
{
    function _get_trace()
    {
        if(!isset($this->trace))
            $this->trace = new Trace();

        return $this->trace;
    }

    function _execute($sql)
    {
        $x = parent::_execute($sql);

        $this->_get_trace()->add_query($sql, $this->insert_id(), $this->affected_rows());

        return $x;
    }

}
/* End of file mysql_driver.php */
/* Location: ./system/database/drivers/mysql/mysql_driver.php */