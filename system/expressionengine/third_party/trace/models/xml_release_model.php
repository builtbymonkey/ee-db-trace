<?php
/**
 * @package		DB Trace Module
 * @author		Frans Cooijmans, dWise
 * @copyright           Copyright (c) 2014 dWise
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt
 * @link		http://www.dwise.nl
 */

require_once 'xml_model.php';

class Xml_release_model extends Xml_model
{

    private $_release = FALSE;
    private $_queries = FALSE;

    function load($file, $create_if_not_exists = FALSE)
    {
        if(!parent::load($file, $create_if_not_exists))
            return FALSE;

        $this->_release = $this->_get_single_node('release');

        if(!$this->_release)
            $this->_release = $this->_add_node('release');

        $this->_queries = $this->_get_single_node('queries', $this->_release);

        if(!$this->_queries)
            $this->_queries = $this->_add_node('queries', $this->_release);

        return TRUE;
    }

    function set_meta($date, $developer, $source_url, $source_path, $description, $md5)
    {
        //cleanup old meta data
        $meta = $this->_get_single_node('meta');

        if($meta)
            $this->_release->removeChild($meta);

        //create new meta data       
        $meta = $this->_add_node('meta', $this->_release);

        $date = $this->_add_text_node($meta, 'date', $date);
        $developer = $this->_add_text_node($meta, 'developer', $developer);
        $source_url = $this->_add_text_node($meta, 'source_url', $source_url);
        $source_path = $this->_add_text_node($meta, 'source_path', $source_path);
        $description = $this->_add_text_node($meta, 'description', $description);
        $md5 = $this->_add_text_node($meta, 'md5', $md5);
    }

    function get_meta()
    {
        $meta = $this->_get_single_node('meta');

        $result = array();
        $result['date'] = $this->_get_single_node_content('date', $meta);
        $result['developer'] = $this->_get_single_node_content('developer', $meta);
        $result['description'] = $this->_get_single_node_content('description', $meta);
        $result['source_url'] = $this->_get_single_node_content('source_url', $meta);
        $result['source_path'] = $this->_get_single_node_content('source_path', $meta);
        $result['md5'] = $this->_get_single_node_content('md5', $meta);
      
        $result['file'] = basename($this->file, '.xml');
        $result['installed'] = 0;

        return (object) $result;
    }

    function add_query(&$query)
    {
        $temp = $this->doc->importNode($query, TRUE);

        $this->_queries->appendChild($temp);
    }
    
    function get_query_batches()
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
                            'affected_rows' => $query->getAttribute('affected_rows'),
                            'status' => 'Queued',
                            'message' => FALSE);
            }

            $result[] = $row;
        }

        return $result;
    }

}
?>
