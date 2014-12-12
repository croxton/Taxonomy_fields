<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
require_once PATH_THIRD. 'taxonomy/fieldtypes/ft.taxonomy_assets_base' . EXT;

class Taxonomy_assets_file_ft extends Taxonomy_assets_base_ft {

    /**
     * display_name
     * @var string
     */
    public $display_name = 'Assets - file';

    /**
     * allowed_file_kind
     * @var boolean
     */
    protected $allowed_file_kind = 'any';

    /**
     * Generate markup for the custom field
     *
     * @access  protected
     * @param   string 
     * @param   string 
     * @param   string 
     * @return  string 
     */
    protected function field_html($name, $value, $url)
    {
        if ( ! empty($value))
        {
            $html = '<div id="cf-'.$name.'-preview"><p>'.$url.'</p></div>
                    <button id="cf-'.$name.'-select">Select</button> &nbsp;
                    <button id="cf-'.$name.'-delete">Remove</button>
            ';
        } 
        else
        {
            $html = '<div id="cf-'.$name.'-preview"><p></p></div>
                    <button id="cf-'.$name.'-select">Select</button> &nbsp;
                    <button id="cf-'.$name.'-delete" style="display: none;">Remove</button>
            ';
        }

        $html = form_hidden('node[field_data]['.$name.']', $value, 'id=cf-'.$name) . $html;

        return $html;
    }
 
}