<?php if( $mode=='config' ): ?>
info: you can redirect your page to another section in your site or to externel site externel has the high periority
section: 
	type:section 
externel: 
	type:textbox 
	label:Externel URL 

<?php elseif( $mode=='layout' ): ?>
0


<?php elseif( $mode=='view' ): ?>
<?php
$c = new Content();
$c->get_by_id( $id );

$u = ( empty($info->externel) )? site_url($info->section) :	$info->externel;

if( ($ci->system->mode=='edit') and $ci->ion_auth->is_admin() )
{
	$ci->load->library('gui');
	echo $ci->gui->info( 'Redirect content here to this '. anchor( $u, 'Page' ) );
}
else
{
	redirect( $u );
}

?>
<?php endif; ?>
