<?php
/**
 * @package		DB Trace Module
 * @author		Frans Cooijmans, dWise
 * @copyright           Copyright (c) 2014 dWise
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt
 * @link		http://www.dwise.nl
 */
require_once 'xml_model.php';

class Xml_trace_model extends Xml_model
{

    private $_queries = FALSE;

    function load($file, $create_if_not_exists = FALSE)
    {
        
        
        if(!parent::load($file, $create_if_not_exists))
            return FALSE;
        

        $xml_queries = $this->_get_single_node('queries');

        if(!$xml_queries)
        {
            $xml_queries = $this->doc->createElement("queries");
            $this->doc->appendChild($xml_queries);
        }

        $this->_queries = & $xml_queries;
        
        
        return TRUE;
    }

    function get_all_query_batches()
    {
        $xml_batches = $this->_get_nodes('query_batch');

        $result = array();

        foreach($xml_batches as $batch)
        {
            $row = (object) array('md5' => $batch->getAttribute('md5'),
                        'date' => $batch->getAttribute('date'),
                        'uri' => $this->_get_single_node_content('uri', $batch),
                        'queries' => array());
            $queries = $batch->getElementsByTagName("query");

            foreach($queries as $query)
            {
                $row->queries[] = (object) array('sql' => $query->textContent,
                            'md5' => $query->getAttribute('md5'),
                            'insert_id' => $query->getAttribute('insert_id'),
                            'affected_rows' => $query->getAttribute('affected_rows'));
            }

            $result[] = $row;
        }

        return $result;
    }

    function get_one_query_batch($md5)
    {
        $xml_query_batch = $this->_find_query_batch($md5);

        if(!$xml_query_batch)
        {
            $xml_query_batch = $this->doc->createElement("query_batch");

            $this->_add_attribute($xml_query_batch, 'md5', $md5);
            $this->_add_attribute($xml_query_batch, 'date', gmmktime());
            $this->_add_cdata_node($xml_query_batch, 'uri', $_SERVER["REQUEST_URI"]);

            $this->_queries->appendChild($xml_query_batch);
        }

        return $xml_query_batch;
    }

    function insert_query($md5, $sql, $inser_id, $affected_rows)
    {
        $query_batch = $this->get_one_query_batch($md5);

        $xml_query = & $this->_add_cdata_node($query_batch, 'query', $sql);

        $this->_add_attribute($xml_query, 'md5', md5($sql . time()));
        $this->_add_attribute($xml_query, 'insert_id', $inser_id);
        $this->_add_attribute($xml_query, 'affected_rows', $affected_rows);
    }

    private function _find_query_batch($md5)
    {
        $xpath = new DomXpath($this->doc);

        $result = $xpath->query('//query_batch[@md5="' . $md5 . '"]');

        if($result->length > 0)
            return $result->item(0);

        return FALSE;
    }

    function find_queries($md5_hashes)
    {
        $result = array();
        
        $queries = $this->_get_nodes('query_batch');
        
        foreach($queries as $query)
        {
            $md5 = $query->getAttribute("md5");

            if(in_array($md5, $md5_hashes))
            {
                $result[] = $query;
            }
        }

        return $result;
    }

}