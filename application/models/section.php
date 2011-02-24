<?php
/**  \addtogroup Models
 * Section class: a datamapper class that holds page data from the database
 *
 * @package	Vunsy
 * @subpackage	Vunsy
 * @category	model file
 * @author	Emad Elsaid
 * @link	http://github.com/blazeeboy/vunsy
 */
class Section extends DataMapper {
	var $default_order_by = array('sort');
	var $ci;

	function __construct($id=NULL){
		parent::__construct($id);
		$this->ci =& get_instance();
	}

	/**
	 * get all the parent of that section up to
	 * the index page as an array of IDs
	 **/
	function get_parents(){

		$c = new Section($this->id);

		$parents = array();
		while( !empty($c->parent_section) ){
			$c = $c->get_by_id( $c->parent_section );
			array_push( $parents, $c->id );
		}
		return $parents;
	}

	function save( $object= '' ){

		if( empty($this->id) and empty($object) ){
			$s = new Section();
			$s->where( 'sort >=', $this->sort );
			$s->get();

			foreach( $s as $item ){
				$item->sort++;
				$item->save();
			}
		}

		parent::save($object);
	}

	/**
	 * delete that section with all it's subsections
	 **/
	function delete( $object = '', $related_field = '' ){

		// update all the sections sort after that section
		// that in the same parent section
		$s = new Section();
		$s->where( 'sort >', $this->sort );
		$s->where( 'parent_section', $this->parent_section );
		$s->get();

		foreach( $s as $item ){
			$item->sort--;
			$item->save();
		}

		//delete this section
		parent::delete( $object, $related_field );

	}

	/**
	 * attach the object to that section and under the parent in cell with order sort
	 * @param	$object: content object we want to attach
	 * @param	$parent: content parent object
	 * @param	$cell: cell in parent content we want to attach in it
	 * @param	$sort: order of the content in it's cell
	 **/
	function attach( $object ){

		$cont = new Content();
		// put the content in it's place require change all it's
		// sisters that has a greater sort number to be increased
		// get all this content belong to this parent and this section
		// and the same cell and has a sort number greater that this
		// sort number
		$cont->where( 'parent_content', $object->parent_content );//same parent
		$cont->where( 'cell', $object->cell );// same cell
		$cont->where( 'sort >=', $object->sort) ;//greater sort
		$cont->get();//get them to process
		foreach( $cont as $item ){
			$item->sort++;
			$item->save();
		}

		//save the object itself
		$object->save();
	}

	/**
	 * attach $section to the current section object
	 * @param	$section: 	section object we want to attach
	 * 					as a child to current section
	 **/
	function attach_section( $section='' ){

		// check if that place it took
		$cont = new Section();
		$cont->where( 'parent_section', $this->id );//same section
		$cont->where( 'sort', $section->sort );//greater sort
		$cont->get();//get them to process

		if( $cont->exists() ){
				
			$cont->where( 'parent_section', $this->id );//same section
			$cont->where( 'sort >=', $section->sort );//greater sort
			$cont->get();//get them to process
			foreach( $cont as $item ){
				$item->sort++;
				$item->save();
			}

		}

		$section->save();
	}

	/**
	 * deattach the content object from current section object
	 * and it's parent
	 * @param	$object: content object we want to deattach
	 **/
	function deattach( $object='' ){

		//=========================
		//normalize the  sort numbers
		//=========================
		$cont = new Content();
		// we have to push all the content up to fill that hole
		// these content must me in the same section,parent,cell
		// and have sort nubmer greater than that content
		$cont->where( 'parent_content', $object->parent_content );//same parent
		$cont->where( 'cell', $object->cell );// same cell
		$cont->where( 'sort >', $object->sort );//greater sort
		$cont->get();//get them to process
		foreach( $cont as $item ){
			$item->sort--;
			$item->save();
		}
			
	}

	/**
	 * deattach section object from current section
	 * @param	$section: section we want to deattach
	 **/
	function deattach_section( $section='' ){

		//=========================
		//normalize the  sort numbers
		//=========================
		$cont = new Section();
		// we have to push all the content up to fill that hole
		// these content must me in the same section,parent,cell
		// and have sort nubmer greater than that content
		$cont->where( 'parent_section', $section->parent_section );//same section
		$cont->where( 'sort >', $section->sort );//greater sort
		$cont->get();//get them to process
		foreach( $cont as $item ){
			$item->sort--;
			$item->save();
		}
			
	}

	/**
	 * return true if that user can view the section
	 * and false if cannot view it
	 **/
	function can_view(){
		return (empty($this->view) or perm_chck( $this->view ));
	}

	/**
	 * render the HTML of current section
	 * that function works if that section is the current
	 * section of the user
	 **/
	function render(){

		if($this->ci->vunsy->section->can_view()){
			/*********************************************
			 *  redering the page BODY content
			 * here i open the edit mode so the widgets got the
			 * container box and the controller buttons
			 * and the admin toolbar
			 * ********************************************/
			$page_body = new Content();
			$page_body->get_by_info( 'PAGE_BODY_LOCKED' );
			$page_body_text = $page_body->render();

			// adding the admin toolbar
			if( $this->ci->ion_auth->is_admin())
			$page_body_text .= $this->ci->load->view( 'edit_mode/toolbar', '', TRUE );

			$doctype_text = doctype( $this->ci->config->item('doctype') );
			/*********************************************************
			 * display the page content
			 * i sum all the page content text
			 * before page + CSS + JS + head + body + after page
			 * *******************************************************/
			// Rendering the page
			echo <<<EOT
			{$doctype_text}
<html xmlns="http://www.w3.org/1999/xhtml" >
	<head>
	<title>{$this->ci->config->item('site_name')} {$this->name}</title>
	<meta http-equiv="content-type" content="text/html;charset={$this->ci->config->item('charset')}" />
	<meta name="generator" content="VUNSY system" />
	{$this->ci->vunsy->css_text()}
	{$this->ci->vunsy->js_text()}
	{$this->ci->vunsy->dojo_text()}
	{$this->ci->vunsy->header_text()}
	</head>
	<body class="{$this->ci->vunsy->dojoStyle}">
	{$page_body_text}
	</body>
</html>
EOT;
		}else{
			show_error( 'Access denied' );
		}
	}
}