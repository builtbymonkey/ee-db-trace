<?php
/**
 * @package		DB Trace Module
 * @author		Frans Cooijmans, dWise
 * @copyright           Copyright (c) 2014 dWise
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt
 * @link		http://www.dwise.nl
 */

class Xml_model
{

    protected $doc;
    protected $file;

    function __construct()
    {
        $this->doc = new DOMDocument();
        $this->doc->formatOutput = true;
        $this->doc->preserveWhitespace = false;
    }

    function get_xml_doc()
    {
        return $this->doc;
    }

    function load($file, $create_if_not_exists = FALSE)
    {
        $this->file = $file;
        
        if(file_exists($file))
        {
           if($this->doc->load($file))
               return TRUE;
        }
        else
        {
            if($create_if_not_exists)
                if(is_writable(dirname($file)))
                    return TRUE;
        }
        
        return FALSE;
    }
    

    function save()
    {
       //simplify return value
       if($this->doc->save($this->file)===FALSE)
            return FALSE;
       else
           return TRUE;
       
    }

    protected function _add_text_node(&$parent, $name, $value)
    {
        $xml = $this->doc->createElement($name);
        $xml->appendChild($this->doc->createTextNode($value));
        $parent->appendChild($xml);

        return $xml;
    }

    function _add_node($name, &$parent = FALSE)
    {
        $xml_node = $this->doc->createElement($name);

        if($parent)
            $parent->appendChild($xml_node);
        else
            $this->doc->appendChild($xml_node);
        
        return $xml_node;
    }

    protected function _add_cdata_node(&$parent, $name, $value)
    {
        $xml = $this->doc->createElement($name);
        $xml->appendChild($this->doc->createCDATASection($value));
        $parent->appendChild($xml);

        return $xml;
    }

    protected function _add_attribute(&$parent, $name, $value)
    {
        $xml_attr = $this->doc->createAttribute($name);
        $xml_attr->value = $value;
        $parent->appendChild($xml_attr);

        return $xml_attr;
    }

    protected function _get_single_node_content($name, &$parent = FALSE)
    {
        $node = $this->_get_single_node($name, $parent);

        if($node)
            return $node->textContent;
        else
            return FALSE;
    }

    protected function _get_single_node($name, &$parent = FALSE)
    {
        if(!$parent)
            $parent = & $this->doc;

        $node = $parent->getElementsByTagName($name);

        if($node->length > 0)
            return $node->item(0);
        else
            return FALSE;
    }
    
    protected function _get_nodes($name, &$parent = FALSE)
    {
         if(!$parent)
            $parent =& $this->doc;
         
        $nodes =  $parent->getElementsByTagName($name);
        
         if($nodes->length > 0)
            return $nodes;
        else
            return FALSE;
    }

}
?>
