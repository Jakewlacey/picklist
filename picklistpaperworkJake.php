<?php
function picklistpaperworkJake_tid_to_letter($tid = null)
{
	switch ($tid)
	{
		case 100: return "E"; break;
		case 101: return "P"; break;
		case 116: return "T"; break;
		default : return "B"; break;
	}
}
function picklistpaperworkJake_master_type_for_row($decoration_types =array())
{
	//picklistpaperworkJake_tid_to_letter
	#krumo ($decoration_types);
	
	///if any embroidery then return E
	//if gets this far then any P then return P
	//if gets this far then any T then return T
	if (isset($decoration_types[100])){ return picklistpaperworkJake_tid_to_letter(100);}
	if (isset($decoration_types[101])){ return picklistpaperworkJake_tid_to_letter(101);}
	if (isset($decoration_types[116])){ return picklistpaperworkJake_tid_to_letter(116);}
	return picklistpaperworkJake_tid_to_letter(null);
	
}

function picklistpaperworkJake()
{

	$args=arg();
	#krumo ($args);
	$order_id = isset($args[3])?$args[3]:0;
	$picklist_nid= isset($args[4])?$args[4]:0;
	
	$order = uc_order_load($order_id);
	$picklist = node_load($picklist_nid);
	
	//krumo($picklist);

	$picklist_items = picklist_orderdetails_items($picklist_nid, false);


	//going to have an array to hold all inform for the picklist
	$picklist_info = array();

	//krumo ($order);
	#krumo ($picklist_node);
	#krumo ($picklist_nid);
	#krumo ($order_id);
	#

	
	/*
	 * E - EMBROIDERY
	 * T - TRANSFER
	 * P - PRINT
	 * B - BASIC/PLAIN
	 *  */
	$open_decorations = array();
	$section =array('E'=>array(), 'T'=>array(), 'P'=>array(),'B'=>array());
	$decorations=array('E'=>array(), 'T'=>array(), 'P'=>array());
	$decorations_print=array('E'=>array(), 'T'=>array(), 'P'=>array());

	$picklist_items = picklist_orderdetails_items($picklist_nid, false);
	#krumo ($picklist_items);
	
	$total_qty = 0;
	$missing_qty = 0;
	$school_list_for_items=array();
	$embroidery = 0 ; $print = 0; $transfer = 0; $plain = 0;
	$lineoddeven = " lineodd ";
	$items_counter = 0;
	
	
	foreach ($picklist_items as $pnid => $item_data)
	{
		#krumo ($item_data);
		$field_wpi_needs_decorating =isset($item_data->field_wpi_needs_decorating['und'][0]['value'])?$item_data->field_wpi_needs_decorating['und'][0]['value']:0; #tells us the row has decorations
		$field_wpi_decorated_stock_ready =isset($item_data->field_wpi_decorated_stock_ready['und'][0]['value'])?$item_data->field_wpi_decorated_stock_ready['und'][0]['value']:0; #tells us if the row has already been decorated ///i.e 
		$school_code  = "";
		$decoration_grouping = "B";
		$item_data['node']->decoration_types = null;
		///info about base product 
		$base_product = nodetitle($item_data['node']->field_wpi_base_product['und'][0]['nid']);
		if ($base_product == null){ $base_product = nodetitle($item_data['node']->field_wpi_product ['und'][0]['nid']) ;}
		$item_data['node']->base_product=$base_product; 
		////stock location 
		$pickloc = picklist_pickinglocation($item_data['node']->field_wpi_stock_assigned['und'][0]['value']);
		$item_data['node']->stock_location = 	$pickloc;	
		
		/* product attributes*/
		$attributes = array();
		#krumo ($item_data['node']->field_wpi_product_attributes);
		if(isset($item_data['node']->field_wpi_product_attributes['und'][0]['value']))
		{
			foreach ($item_data['node']->field_wpi_product_attributes['und'] as $options)
			{
				$attributes [aw_attribute_option_aid($options['value'])]= aw_attribute_option_name($options['value']); 
			}
		}
		$item_data['node']->attributes = 	$attributes;	
		
		
		if (isset ($item_data['node']->field_wpi_decorations['und'] [0]['nid']))
		{
			$decoration_types = array();
			foreach ($item_data['node']->field_wpi_decorations['und'] as $decoration_item)
			{
				#krumo ($decoration_item);
				if (isset($decoration_item['nid']))
				{
					///if we've alerady haven't loaded  the decoration details before, then we can get the details, and add it to the array to be used over and over without reloading from the db
					if(!isset($open_decorations[$decoration_item['nid']]))
					{
						$ddata = picklist_decoration_details($decoration_item['nid']); //load the basic details
						$open_decorations[$decoration_item['nid']]=$ddata;  ///store the basic details into the used deocrations array
						$decorations[picklistpaperworkJake_tid_to_letter($open_decorations[$decoration_item['nid']]['type'])]= $open_decorations[$decoration_item['nid']];
					}
					$decoration_types[$open_decorations[$decoration_item['nid']]['type']]=$open_decorations[$decoration_item['nid']]['type'];
					
					$decoration_list[picklistpaperworkJake_tid_to_letter($open_decorations[$decoration_item['nid']]['type'])][$decoration_item['nid']]=$open_decorations[$decoration_item['nid']];
					
				}
			}///ends foreach loop 
		$item_data['node']->decoration_types = $decoration_types; //store the gathered data into the item data
		/////////////////WHAT TYPE OF DECORATION ARE WE GOING TO PUSH THIS ROW TOO, E T OR P
		$decoration_grouping = picklistpaperworkJake_master_type_for_row($decoration_types);



		#krumo ($decoration_grouping, $decoration_types);print "<BR>----";

		} ///end IF has decorations loop
		
		
		
		$sections[$decoration_grouping][]=$item_data;
	}
	#krumo ($decorations);
	//krumo ($sections);


	//// page display
	//
	$school = node_load($order->field_invoice_school['und'][0]['nid']);

	$picklist_paperwork = file_get_contents(dirname(__FILE__). "/picklistpaperworkJake.html");
  	$picklist_paperwork = str_replace('[page_num]', $page_num, $picklist_paperwork);

	$picklist_info['acc_no'] = isset($school->field_s_customer_code['und'][0]['safe_value']) ? $school->field_s_customer_code['und'][0]['safe_value'] : null;
	$picklist_info['name'] = isset($school->title) ? $school->title : null;
	$picklist_info['order_code'] = isset($order->field_invoice_buyer_type['und'][0]['value']) ? $order->field_invoice_buyer_type['und'][0]['value'] : null;
	$picklist_info['received_date'] = isset($order->created) ? format_date($order->created, "custom", "d/m/Y") : null;
	$picklist_info['order_no'] = isset($order->order_id)? $order->order_id : null;
	$picklist_info['request_date'] = isset($order->field_invoice_due_date['und'][0]['value']) ? format_date($order->field_invoice_due_date['und'][0]['value'], "custom", "d/m/Y") : null;
	$picklist_info['order_value'] = isset($order->order_total)? "&pound{$order->order_total}" : null;
	$picklist_info['address'] = isset($order) ? picklist_delivery_address_order($order, ", ") : null;
	$picklist_info['job_no'] = isset($picklist->title)? $picklist->title : null;
	
	$picklist_info['picklist_print_date'] = isset($picklist->field_wp_first_print_date['und'][0]['safe_value']) ? $picklist->field_wp_first_print_date['und'][0]['safe_value'] : null; #date('d/m/Y');
	
	/**
	 THESE BELOW STILL NEED ADDING !!!
	 */
	$picklist_info['packing_option'] = isset($school->field_s_bagging_option['und'][0]['tid']) ? $school->field_s_bagging_option['und'][0]['tid'] : null;
	$picklist_info['picklist_barcode'] = NULL;
	$picklist_info['dual_multi'] = NULL;
	$picklist_info['route'] = NULL;
	$picklist_info['picklist_name'] = NULL;
	$picklist_info['order_type'] = NULL;
	/**
	 THESE ABOVE STILL NEED ADDING !!!
	 */

	//fetch the special instructions
	foreach ($decorations as $decor_type) {
		$decor_node = node_load($decor_type['nid']);
		$picklist_info['special_instructions'][$decor_type['nid']] = isset($decor_node->field_d_instructions['und'][0]['value']) ? $decor_node->field_d_instructions['und'][0]['value'] : null;
	}

	$picklist_info['decoration_list'] = $decoration_list;

	$picklist_info['sections']['E'] = isset($sections['E']) ? picklistpaperworkJake_build_category_row($sections['E']): null;
	$picklist_info['sections']['P'] = isset($sections['P']) ? picklistpaperworkJake_build_category_row($sections['P']): null;
	$picklist_info['sections']['T'] = isset($sections['T']) ? picklistpaperworkJake_build_category_row($sections['T']): null;
	$picklist_info['sections']['B'] = isset($sections['B']) ? picklistpaperworkJake_build_category_row($sections['B']): null;
	
	// need to find total products && Total logo's
	$picklist_info['sections']['E']['product_count'] = picklistpaperworkJake_add_product_total($picklist_info['sections']['E']);
	$picklist_info['sections']['P']['product_count'] = picklistpaperworkJake_add_product_total($picklist_info['sections']['P']);
	$picklist_info['sections']['T']['product_count'] = picklistpaperworkJake_add_product_total($picklist_info['sections']['T']);
	$picklist_info['sections']['B']['product_count'] = picklistpaperworkJake_add_product_total($picklist_info['sections']['B']);

	$picklist_info['total_products'] = picklistpaperworkJake_get_total_products($picklist_info['sections']);

	#krumo($order);
	#krumo($picklist);
	#krumo($school);

	picklistpaperworkJake_render_page($picklist_info);
	picklistpaperworkJake_render_decoration($picklist_info);

	#krumo($picklist_info);

	print $picklist_paperwork;

	foreach ($picklist_info['paged_rendered'] as $page) {
		print $page;	
	}

	foreach ($picklist_info['decoration_list_rendered'] as $page) {
	print $page;
	}


	return "";
}

function picklistpaperworkJake_render_decoration(&$picklist_info) {
	$template = file_get_contents(dirname(__FILE__). "/picklistpaperworkDecoration.tpl.html");

	foreach ($picklist_info as $replacee => $replacement) {
		$template = str_replace("[$replacee]", $replacement, $template);
	}

	$picklist_info['decoration_info'] = picklistpaperworkJake_decoration_info($picklist_info);
	$picklist_info['decoration_pages'] = picklistpaperworkJake_render_decoration_rows($picklist_info);

	foreach ($picklist_info['decoration_pages'] as $page_num => $page_info) {
		$templ = str_replace('[decorations]', $page_info, $template);
		$picklist_info['decoration_list_rendered'][$page_num] = $templ;
	}


	#paged headers & content
	#oreach ($picklist_info['pages'] as $page_num => $page_info) {
	#	$templ = str_replace('[picklist_items]', $page_info, $template);
	#	$templ = str_replace('[page_num]', $page_num, $templ);
	#	$templ = str_replace('[page_total]', $page_total, $templ);
	#	$templ = str_replace('[special_instructions_array]', $special_instructions, $templ);
	#	$picklist_info['paged_rendered'][$page_num] = $templ;
	#}
	
}

function picklistpaperworkJake_render_decoration_rows($picklist_info) {
	
	$rows = "";
	$paged_rows = array();
	$max_rows = 3;
	$page_count = 1;
	$rows_count = 1;

	foreach ($picklist_info['decoration_info'] as $section => $section_info) {
		
		$section_title = getSection($section);

		$rows .= "<tr><th class=\"no-border\">$section_title</th></tr>";
		foreach ($section_info as $decoration) {
			$rows .= "
					<tr>
						<td>".$decoration['screen_ref']."</td>
						<td>".$decoration['special_instructions']."</td>
						<td>".$decoration['ink']."</td>
						<td><img src=\"".$decoration['image']."\"/></td>
						<td>$rows_count</td>
					</tr>
					";
				$rows_count ++;
				if ($rows_count >= $max_rows) {
		       		$paged_rows[$page_count] = $rows;
		       		unset($rows);
		       		$page_count++;
		       		$rows_count = 1;
		       	}	
		}
	}

	if ($rows != null) {
		$paged_rows[$page_count] = $rows;
		unset($rows);
	}

	return $paged_rows;
}

function picklistpaperworkJake_render_page(&$picklist_info) {
	
	$template = file_get_contents(dirname(__FILE__). "/picklistpaperworkHeader.tpl.html");

	foreach ($picklist_info as $replacee => $replacement) {
		$template = str_replace("[$replacee]", $replacement, $template);
	}

	$picklist_info['pages'] = picklistpaperworkJake_render_rows($picklist_info);
	
	$page_total = count($picklist_info['pages']);

	#special instructions of each page header
	$special_instructions = "";
	foreach($picklist_info['special_instructions'] as $special_index => $special_info) {
		$special_instructions .= $special_info . "</br>";
	}

	#paged headers & content
	foreach ($picklist_info['pages'] as $page_num => $page_info) {
		$templ = str_replace('[picklist_items]', $page_info, $template);
		$templ = str_replace('[page_num]', $page_num, $templ);
		$templ = str_replace('[page_total]', $page_total, $templ);
		$templ = str_replace('[special_instructions_array]', $special_instructions, $templ);
		$picklist_info['paged_rendered'][$page_num] = $templ;
	}

	#return $template;
}

function picklistpaperworkJake_render_rows($picklist_info) {
	$rows = "";

	$max_rows = 13; #11;
	$page_count = 1;
	$paged_rows = array();

	$rows_count =1;
	$picklist_info['sections'];

	foreach ($picklist_info['sections'] as $section => $section_info) {
		
	   $section_title = getSection($section);

       $rows .= ($section_info['product_count']['product_total'] > 0) ?"<tr><td colspan=\"10\" class=\"section-title no-border on-odd-even\">$section_title</td></tr>" : null;
       foreach ($section_info['products'] as $item) {

       		$decoration_list = "";

   			foreach ($item['decorations'] as $decoration) {
   				$decoration_list .= " " . $decoration;
   			}


	       $rows .= "	
	       				<tr class=\"".$row_class."\">
	       					<td class=\"col-0\">". $item['location']. "</td>
	       					<td class=\"col-1\">". $item['quantity']."</td>
	       					<td class=\"col-2\">". $item['proc']."</td>
	       					<td class=\"col-3\">". $item['pack']."</td>
	       					<td class=\"col-4\">". $decoration_list ."</td>
	       					<td class=\"col-5\">". $item['item']."</td>
	       					<td class=\"col-6\">". $item['colour']."</td>
	       					<td class=\"col-7\">". $item['size']."</td>
	       					<td class=\"col-8\">". $item['additional_info']."</td>
	       					<td class=\"col-9\">". $item['sku']."</td>
	       				</tr>
	       			";
	       	$rows_count ++;

	       	if ($rows_count >= $max_rows) {
	       		$paged_rows[$page_count] = $rows;
	       		unset($rows);
	       		$page_count++;
	       		$rows_count = 1;
	       	}	
       }
       
       if (isset($section_info['classification'])) {
       		// probably needs more than adding just one
       		$rows_count++;
       		
       		$rows .= "<tr class=\"no-border\">
       					<td class=\"no-border no-odd-even\"></td>
       					<td>". $section_info['product_count']['product_total'] ."</td>
       					<td colspan=\"8\" class=\"no-border text-left\">Total Products</td>
       				</tr>
       				<tr class=\"no-border\">
       					<td class=\"no-border no-odd-even\"></td>
       					<td>". $section_info['product_count']['logo_total'] ."</td>
       					<td colspan=\"8\" class=\"no-border text-left\">Total Logo's</td>
       				</tr>";
       }
       
       if ($rows_count >= $max_rows) {
       	 $paged_rows[$page_count] = $rows;
       	 unset($rows);
       	 $page_count++;
       	 $rows_count = 0;
       }
       // what happends when this is false when the content hassent broken the limit but is on the final array   
	}

	//get the access of the last page
	if ($rows != null) {
		$paged_rows[$page_count] = $rows;
		unset($rows);
	}

	return $paged_rows;
}


function picklistpaperworkJake_decoration_info($picklist_info) {

	$decoration_list = array();

	//get rows information
	foreach ($picklist_info['decoration_list'] as $section => $section_info) {

		$section_title = getSection($section);

		foreach($section_info as $node_raw) {
			$node = node_load($node_raw['nid']);

			$decoration_list[$section][$node_raw['nid']]['screen_ref'] = isset($node->field_d_screen_reference['und'][0]['value']) ? $node->field_d_screen_reference['und'][0]['value'] : null;
			$decoration_list[$section][$node_raw['nid']]['special_instructions'] = isset($node->field_d_instructions['und'][0]['value']) ? $node->field_d_instructions['und'][0]['value'] : null;
			$decoration_list[$section][$node_raw['nid']]['inks'] = isset($node->field_d_screen_reference['und'][0]['value']) ? $node->field_d_screen_reference['und'][0]['value'] : null;
			$decoration_list[$section][$node_raw['nid']]['image'] = isset($node->field_decor_image['und'][0]['uri']) ? file_create_url($node->field_decor_image['und'][0]['uri']) : null;
		}	
	}

	return $decoration_list;
}

function picklistpaperworkJake_build_category_row($section = array()) {

	$items = array();

	foreach ($section as $item) {
		$nid = $item['nid'];

		$items['classification'] = $item['node']->field_wpi_type_rating['und'][0]['value'];
		$items['pick_location'] = picklist_pickinglocation($item['node']->field_wpi_stock_assigned['und'][0]['value']);

		$attributes = array();

		foreach ($item['node']->field_wpi_product_attributes['und'] as $index => $options) {
			$option = aw_attribute_option_name_for_picklist($options['value']);
			$key = $option['aid']==6? 'colour':'size';
			$attributes[$key] = $option['name'];
		}

		$item_node = node_load($item['node']->field_wpi_base_product['und'][0]);

		#krumo($item);
		#krumo($item_node);

		$item_info = array(
			'location' => $items['pick_location']['details']['locno'] . "." . $items['pick_location']['details']['aisle'],
			'quantity' => $items['pick_location']['qty'],
			'proc' => '',
			'item' => $item_node->title,
			'sku' => $items['pick_location']['sku'],
			'colour' => $attributes['colour'],
			'size' => $attributes['size'],
			'decorations' => $item['node']->decoration_types,
			'check' => '',
			'additional_info' => '',
			'pack' => ''
		);

		$items['products'][$nid] = $item_info;
		
	}
	return $items;
}

function picklistpaperworkJake_get_total_products($picklist_info) {
	$product_count = 0;

	foreach ($picklist_info as $item) {
		$product_count += $item['product_count']['product_total'];
	}

	return $product_count;
}

function picklistpaperworkJake_add_product_total($picklist_info) {
	$qyt = array();
	$qty['product_total'] = 0; 
	$qty['logo_total'] = 0;

	foreach ($picklist_info['products'] as $item => $item_info) {
		$qty['product_total'] += $item_info['quantity'];
		$qty['logo_total'] += $item_info['quantity'] * count($item_info['decorations']); 
	}

	return $qty;
}

function getSection($section) {
	switch ($section) {
			case 'E':
				return "Embriodered";
				break;
			case 'P':
				return "Printed";
				break;
			case 'T':
				return "Transferred";
				break;
			case 'B':
				return "Plain";
				break;
		}
}