<?php
/**
 * Add Forum Options
 * Copyright 2010 Starpaul20
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Tell MyBB when to run the hooks
$plugins->add_hook("postbit", "addforumoptions_post");
$plugins->add_hook("postbit_prev", "addforumoptions_post");
$plugins->add_hook("showthread_end", "addforumoptions_showthread");
$plugins->add_hook("reputation_add_start", "addforumoptions_reputation");
$plugins->add_hook("reputation_do_add_start", "addforumoptions_reputation");

$plugins->add_hook("admin_formcontainer_output_row", "addforumoptions_forum");
$plugins->add_hook("admin_forum_management_add", "addforumoptions_forum_add");
$plugins->add_hook("admin_forum_management_edit_commit", "addforumoptions_forum_commit");
$plugins->add_hook("admin_forum_management_add_commit", "addforumoptions_forum_commit");

// The information that shows up on the plugin manager
function addforumoptions_info()
{
	return array(
		"name"				=> "Add Forum Options",
		"description"		=> "Adds four additional forum options for control over avatar, signature, quick reply display and post reputation.",
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "2.2.3",
		"guid"				=> "c83be3129f4d2ee537f294ee24216495",
		"compatibility"		=> "16*"
	);
}

// This function runs when the plugin is installed.
function addforumoptions_install()
{
	global $db, $cache;
	addforumoptions_uninstall();

	$db->add_column("forums", "usequickreply", "int(1) NOT NULL default '1'");
	$db->add_column("forums", "allowavatars", "int(1) NOT NULL default '1'");
	$db->add_column("forums", "allowsignatures", "int(1) NOT NULL default '1'");
	$db->add_column("forums", "allowpostreps", "int(1) NOT NULL default '1'");

	$cache->update_forums();
}

// Checks to make sure plugin is installed
function addforumoptions_is_installed()
{
	global $db;
	if($db->field_exists("usequickreply", "forums"))
	{
		return true;
	}
	return false;
}

// This function runs when the plugin is uninstalled.
function addforumoptions_uninstall()
{
	global $db, $cache;
	if($db->field_exists("usequickreply", "forums"))
	{
		$db->drop_column("forums", "usequickreply");
	}

	if($db->field_exists("allowavatars", "forums"))
	{
		$db->drop_column("forums", "allowavatars");
	}

	if($db->field_exists("allowsignatures", "forums"))
	{
		$db->drop_column("forums", "allowsignatures");
	}

	if($db->field_exists("allowpostreps", "forums"))
	{
		$db->drop_column("forums", "allowpostreps");
	}

	$cache->update_forums();
}

// This function runs when the plugin is activated.
function addforumoptions_activate()
{
}

// This function runs when the plugin is deactivated.
function addforumoptions_deactivate()
{
}

// Add to forum management page
function addforumoptions_forum($above)
{
	global $mybb, $lang, $form, $forum_data;
	$lang->load("add_forum_options", true);

	if($above['title'] == $lang->misc_options && $lang->misc_options)
	{
		$above['content'] .="<div class=\"forum_settings_bit\">".$form->generate_check_box('usequickreply', 1, $lang->use_quick_reply, array('checked' => $forum_data['usequickreply'], 'id' => 'usequickreply'))."</div>";
		$above['content'] .="<div class=\"forum_settings_bit\">".$form->generate_check_box('allowavatars', 1, $lang->allow_avatars, array('checked' => $forum_data['allowavatars'], 'id' => 'allowavatars'))."</div>";
		$above['content'] .="<div class=\"forum_settings_bit\">".$form->generate_check_box('allowsignatures', 1, $lang->allow_signatures, array('checked' => $forum_data['allowsignatures'], 'id' => 'allowsignatures'))."</div>";
		$above['content'] .="<div class=\"forum_settings_bit\">".$form->generate_check_box('allowpostreps', 1, $lang->allow_post_reps, array('checked' => $forum_data['allowpostreps'], 'id' => 'allowpostreps'))."</div>";
	}

	return $above;
}

function addforumoptions_forum_add()
{
	global $forum_data;
	$forum_data['usequickreply'] = 1;
	$forum_data['allowavatars'] = 1;
	$forum_data['allowsignatures'] = 1;
	$forum_data['allowpostreps'] = 1;
}

function addforumoptions_forum_commit()
{
	global $db, $mybb, $cache, $fid;
	$update_array = array(
		"usequickreply" => intval($mybb->input['usequickreply']),
		"allowavatars" => intval($mybb->input['allowavatars']),
		"allowsignatures" => intval($mybb->input['allowsignatures']),
		"allowpostreps" => intval($mybb->input['allowpostreps']),
	);

	$db->update_query("forums", $update_array, "fid='{$fid}'");

	$cache->update_forums();
}

// Remove avatar/signature/post rep on postbit (main and preview postbits only)
function addforumoptions_post($post)
{
	global $forum;
	if($forum['allowsignatures'] != 1)
	{
		$post['signature'] = "";
	}

	if($forum['allowavatars'] != 1)
	{
		$post['useravatar'] = "";
	}

	if($forum['allowpostreps'] != 1)
	{
		$post['button_rep'] = "";
	}

	return $post;
}

// Remove quick reply box on showthread
function addforumoptions_showthread()
{
	global $forum, $quickreply;
	if($forum['usequickreply'] != 1)
	{
		$quickreply = "";
	}
}

// Disallow post reputations
function addforumoptions_reputation()
{
	global $db, $mybb, $lang, $templates, $theme, $headerinclude, $show_back;
	$lang->load("add_forum_options");

	if($mybb->input['pid'])
	{
		$query = $db->query("
			SELECT f.allowpostreps
			FROM ".TABLE_PREFIX."posts p
			LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid=p.fid)
			WHERE p.pid='{$mybb->input['pid']}'
		");
		$forum = $db->fetch_array($query);
		if($forum['allowpostreps'] != 1)
		{
			$message = $lang->post_add_disabled;
			eval("\$error = \"".$templates->get("reputation_add_error")."\";");
			output_page($error);
			exit;
		}
	}
}

?>