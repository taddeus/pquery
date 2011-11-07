<?php

/**
 * Tree data structure, used for rendering purposes.
 * 
 * @package pQuery
 * @author Taddeus Kroes
 * @version 1.0
 */
class Block {
	/**
	 * 
	 * 
	 * @var int
	 */
	static $count = 0;
	
	/**
	 * The unique id of this block.
	 * 
	 * @var int
	 */
	var $id;
	
	/**
	 * The block's name.
	 * 
	 * @var string
	 */
	var $name;
	
	/**
	 * An optional parent block.
	 * 
	 * If NULL, this block is the root of the data tree.
	 * 
	 * @var Block
	 */
	var $parent;
	
	/**
	 * Child blocks.
	 * 
	 * @var array
	 */
	var $children = array();
	
	/**
	 * Variables in this block.
	 * 
	 * All variables in a block are also available in its descendants through {@link get()}.
	 * 
	 * @var array
	 */
	var $vars = array();
	
	/**
	 * Constructor.
	 * 
	 * The id of the block is determined by the block counter.
	 * 
	 * @param string $name The block's name.
	 * @param Block &$parent A parent block (optional).
	 * @see id, name, parent
	 * @uses $count
	 */
	function __construct($name=null, &$parent=null) {
		$this->id = ++self::$count;
		$this->name = $name;
		$this->parent = $parent;
	}
	
	/**
	 * Add a child block.
	 * 
	 * @param string $name The name of the block to add.
	 * @param array $data Data to add to the created block (optional).
	 * @returns Block The created block.
	 */
	function add($name, $data=array()) {
		array_push($this->children, $block = new self($name, $this));
		
		return $block->set($data);
	}
	
	/**
	 * Set the value of one or more variables in the block.
	 * 
	 * @param string|array $vars  Either a single variable name, or a set of name/value pairs.
	 * @param mixed $value The value of the single variable to set.
	 * @returns Block This block.
	 */
	function set($name, $value=null) {
		if( is_array($name) ) {
			foreach( $name as $var => $val )
				$this->vars[$var] = $val;
		} else
			$this->vars[$name] = $value;
		
		return $this;
	}
	
	/**
	 * Get the value of a variable.
	 * 
	 * This method is an equivalent of {@link get()}.
	 * 
	 * @param string $name The name of the variable to get the value of.
	 * @return mixed The value of the variable if it exists, NULL otherwise.
	 */
	function __get($name) {
		return $this->get($name);
	}
	
	/**
	 * Get the value of a variable.
	 * 
	 * @param string $name The name of the variable to get the value of.
	 * @return mixed The value of the variable if it exists, NULL otherwise.
	 */
	function get($name) {
		// Variable inside this block
		if( isset($this->vars[$name]) )
			return $this->vars[$name];
		
		// Variable in one of parents
		if( $this->parent !== null )
			return $this->parent->get($name);
		
		// If the tree's root block does not have the variable, it does not exist
		return null;
	}
	
	/**
	 * Find all child blocks with a specified name.
	 * 
	 * @param string $name The name of the blocks to find.
	 * @returns array The positively matched blocks.
	 */
	function find($name) {
		return array_filter($this->children,
			create_function('$c', 'return $c->name === "'.$name.'";'));
	}
	
	/**
	 * Remove a child block.
	 * 
	 * @param Block &$child The block to remove.
	 * @returns Block This block.
	 */
	function remove_child(&$child) {
		foreach( $this->children as $i => $block ) {
			if( $block->id == $child->id ) {
				array_splice($this->children, $i, 1);
				$block->parent = null;
			}
		}
		
		return $this;
	}
	
	/**
	 * Remove this block from its parent.
	 * 
	 * @returns Block The removed block.
	 */
	function remove() {
		!is_null($this->parent) && $this->parent->remove_child($this);
		
		return $this;
	}
}

?>