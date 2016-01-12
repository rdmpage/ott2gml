<?php

require_once(dirname(__FILE__) . '/adodb5/adodb.inc.php');


//--------------------------------------------------------------------------------------------------
$db = NewADOConnection('mysql');
$db->Connect("localhost", 
	'root' , '' ,'ott');

// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$names = array();
$node_stack = array();
$anc = array();
$packing_type = array();

$node_string = "";
$edge_string = "";



//--------------------------------------------------------------------------------------------------
function get_root($name)
{
	global $db;
	global $names;
	
	$sql = "SELECT * FROM taxonomy WHERE name='$name'";
	$root_id = 0;
	
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
	
	if ($result->NumRows() == 1)
	{
		$root_id = $result->fields['uid'];
		$names[$root_id] = $result->fields['name'];
	}
	
	return $root_id;
}

//--------------------------------------------------------------------------------------------------
// Get immediate children of this node
function get_children($subtree_root_id)
{
	global $anc;
	global $db;
	global $names;
	
	$children = array();

	// Get children		
	$sql = "SELECT * FROM taxonomy WHERE parent_uid=" . $subtree_root_id;
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
	while (!$result->EOF) 
	{
		$children[] = $result->fields['uid'];
		$names[$result->fields['uid']] = $result->fields['name'];
		$anc[$result->fields['uid']] = $subtree_root_id;
		$result->MoveNext();		
	}
	
	//print_r($children);
	
	return $children;
}

//--------------------------------------------------------------------------------------------------
// First time we visit this node, store basic details and initialise weight and depth 
function pre_visit($node_id)
{
	global $names;
	global $node_stack;
	
	global $edge_string;
	global $node_string;

	
	$node = new stdclass;
	$node->weight = 0;
	$node->depth = 0;
	$node->label  = $names[$node_id];
	$node_stack[$node_id] = $node;
	
	
	$node_string .= "node [ id " . $node_id . " label \"" . $names[$node_id] . "\" ]\n";

	
}

//--------------------------------------------------------------------------------------------------
// Last time we visit this node
function post_visit($node_id, $stack, $stack_counter)
{
	global $anc;
	global $names;
	global $node_stack;
	
	global $edge_string;
	
	
	//print_r($node_stack[$node_id]);
	
	
	
	//echo $node_id . ' --> ' . $anc[$node_id] . ' ' . $node_stack[$node_id]->label . "\n";
	
	if ($anc[$node_id] != $node_id)
	{
			$edge_string .= "edge [ source " . $anc[$node_id] . " target " . $node_id . " ]\n";
	}
	
	if ($stack_counter > 0)
	{
		// First item in stack array at level below this level is the ancestor
		$ancestor_id = $stack[$stack_counter-1][0];
		
		// Add this node's weight to that of its ancestor
		$node_stack[$ancestor_id]->weight += $node_stack[$node_id]->weight;
		
		// Update depth of ancestor
		$node_stack[$ancestor_id]->depth 
			= max($node_stack[$ancestor]->depth,
					($node_stack[$node_id]->depth + 1));							
	}
	
	// save memory
	unset ($node_stack[$node_id]);
	unset ($names[$node_id]);
}
	

//--------------------------------------------------------------------------------------------------
// traverse tree using database calls and a stack of arrays
function traverse($name)
{
	global $anc;
	global $names;
	global $node_stack;
	
	global $edge_string;
	global $node_string;
	
	$stack = array();
	
	$root_id = get_root($name);
	$anc[$root_id] = $root_id;
	
	//echo $root_id . "\n";
			
	// start by getting children of root
	$stack[] = array($root_id);
	
	$stack_counter = 0;
	$done = false;
	while (!$done)
	{
		$node = $stack[$stack_counter][0];

		pre_visit($node);
			
		$children = get_children($node);
		if (count($children) > 0)
		{
			// We have children so add them to stack
			$ancestor = $node;
			$stack[] = $children;
			$stack_counter++;
		}
		else
		{
			// No children, so start to unwind stack
			$node_stack[$node]->weight = 1; // leaf
			$node_stack[$node]->depth = 0; // leaf
			
			post_visit($node, $stack, $stack_counter);
			
			// finished with this node	
			array_shift($stack[$stack_counter]);			
			
			// go back down tree
			while (
				($stack_counter >  0)
				&& (count($stack[$stack_counter]) == 0)
				)
			{		
			
				array_pop($stack);
				$stack_counter--;
								
				$node = $stack[$stack_counter][0];
				post_visit($node, $stack, $stack_counter);
								
				array_shift($stack[$stack_counter]);
			}
			
			if ($stack_counter == 0)
			{
				$done = true;
				
				echo "graph\n[directed 1\n";
				echo $node_string;
				echo $edge_string;
				echo "]\n";

			}
			
		}
	}
	
}


$name = 'Cetacea';


traverse($name);




?>