##Taxonomy fields

* Author: [Mark Croxton](http://hallmark-design.co.uk/)

### What is this?

This is a proof-of-concept fieldtype system / hack for [Taxonomy](https://devot-ee.com/add-ons/taxonomy) to allow Taxonomy to be extended with third party custom fieldtypes. At the moment only Wygwam and Assets fieldtypes are supported - but the idea is, you can easily add your own by extending the `Taxonomy_field` abstract class.

### Installation

1. Download and install [Taxonomy](https://devot-ee.com/add-ons/taxonomy).
2. [Download](https://github.com/croxton/Taxonomy_fields/archive/master.zip) the files.
3. Move the `fieldtypes` folder to ./system/expressionengine/third_party/taxonomy/fieldtypes
4. Move the `Taxonomy_field_lib.php` file to ./system/expressionengine/third_party/taxonomy/libraries
5. Make changes to Taxonomy as set out below.

#### ./system/expressionengine/third_party/taxonomy/views/edit_tree.php

Just below this line:
	
	$field_options = array('text'  => 'Text Input', 'textarea'  => 'Textarea',  'checkbox'  => 'Checkbox',);

Add this:

	ee()->load->library('taxonomy_field_lib');
	ee()->load->helper('directory');
	$fieldtypes = directory_map(PATH_THIRD . 'taxonomy/fieldtypes', 1);

	$result = preg_replace('/^ft\.taxonomy_([a-zA-Z0-9_\-]+)'.EXT.'$/i', '$1', $fieldtypes);
	$fieldtypes = array_diff($result, $fieldtypes);

	foreach($fieldtypes as $type)
	{
		// get name
		$ft = ee()->taxonomy_field_lib->load($type);

		if ( NULL !== $ft->display_name)
		{
			$field_options += array($type => $ft->display_name);
		}
	}
	
#### ./system/expressionengine/third_party/taxonomy/mcp.taxonomy.php

At the bottom the constructor `__construct()` add this:

	ee()->load->library('taxonomy_field_lib');
	
At the bottom of `manage_node()` just *before* the return line:

	return $this->_content_wrapper('manage_node', $lang_key, $vars);

Add this:

	foreach($vars['tree']['fields'] as $key => $custom_field)
	{	
		switch($custom_field['type'])
		{	
			// built-in fields
			case 'text' : case 'textarea' : case 'checkbox' :
				break; // do nowt

			// custom field types
			default : 

				// get the field value
				$value = (isset($vars['this_node']['field_data'][ $custom_field["name"] ]))
						 ? $vars['this_node']['field_data'][ $custom_field["name"] ] : '';

				// load the associated fieldtype
				$ft = ee()->taxonomy_field_lib->load($custom_field['type']);

				// generate the field markup
				$vars['tree']['fields'][$key]['html'] = $ft->display_field($custom_field["name"], $value);
				break;
		}
	}
	
Near the top of `update_node()` change this conditional:

	if( isset($node['field_data']) && is_array($node['field_data']))
	{
		$node['field_data'] = json_encode($node['field_data']);
	}

To:

	if( isset($node['field_data']) && is_array($node['field_data']))
	{
		foreach($tree['fields'] as $field)
		{
			switch($field['type'])
			{
				// built-in fields
				case 'text' : case 'textarea' : case 'checkbox' :
					break; // do nowt

				// custom field types
				default : 

					// load the associated fieldtype
					$ft = ee()->taxonomy_field_lib->load($field['type']);

					// alter value?
					$node['field_data'][$field['name']] = $ft->pre_save($node['field_data'][$field['name']]);

					break;
			}
		}
		
		$node['field_data'] = json_encode($node['field_data']);
	}

#### ./system/expressionengine/third_party/taxonomy/mod.taxonomy.php


At the bottom the constructor `__construct()` add this:

	ee()->load->library('taxonomy_field_lib');
	
	
#### ./system/expressionengine/third_party/taxonomy/models/taxonomy_model.php:

Near the top of `get_nodes()` just below this line:

	$nodes = ee()->db->get()->result_array();
	
Add this:

	// map field names => type
	$cf_map = array();
	foreach( $this->cache['trees'][$this->tree_id]['fields'] as $cf)
	{
		$cf_map[$cf['name']] = $cf['type'];
	}

Further down the same function replace this loop:

	foreach($node['field_data'] as $k => $v)
		$node[$k] = $v;
	} 

With:

	foreach($node['field_data'] as $k => $v)
    {	
    	// this should apply to front end template parsing only 
    	$callers=debug_backtrace();
		if ( isset($callers[2]['function']) && $callers[2]['function'] == 'process_tags')
		{
 			switch($cf_map[$k])
 			{
 				// built-in fields
				case 'text' : case 'textarea' : case 'checkbox' :
					break; // do nowt

				// custom field types	
 				default :
 							
 					// load the associated fieldtype
					$ft = ee()->taxonomy_field_lib->load($cf_map[$k]);

					// let the fieldtype change the final value
					$v = $ft->replace_value($v);

					// overwrite value
 					$node['field_data'][$k] = $v;

 					break;
 			}
 		}
 		
		$node[$k] = $v;
	}
	
#### ./system/expressionengine/third_party/taxonomy/views/manage_node.php
Add a default case to this switch control:

	switch($field['type'])
	{
		case 'text':

			echo form_input('node[field_data]['.$field['name'].']', $value, 'id="cf-'.$field['name'].'"');
			break;
		case 'textarea':
				echo form_textarea('node[field_data]['.$field['name'].']', $value, 'id="cf-'.$field['name'].'"');
			break;
		case 'checkbox':
			echo form_checkbox('node[field_data]['.$field['name'].']', 1, $value, 'id="cf-'.$field['name'].'"');
			break;
	}
	
Change it to:

	switch($field['type'])
	{
		case 'text':

			echo form_input('node[field_data]['.$field['name'].']', $value, 'id="cf-'.$field['name'].'"');
			break;
		case 'textarea':
				echo form_textarea('node[field_data]['.$field['name'].']', $value, 'id="cf-'.$field['name'].'"');
			break;
		case 'checkbox':
			echo form_checkbox('node[field_data]['.$field['name'].']', 1, $value, 'id="cf-'.$field['name'].'"');
			break;
			
		default :
			echo $field['html'];
			break; 
	}



