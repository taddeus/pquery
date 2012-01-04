<?php

class CssNode {
	static $shorthands = array(
		'margin' => 'top right bottom left',
		'padding' => 'top right bottom left',
		'font' => 'weight [style] size [/line-height] family',
		'border' => 'width style color',
		'background' => '[color] image repeat [attachment] [position]',
		'list-style' => '[type] [position] [image]',
		'outline' => '[color] [style] [width]'
	);
	static $colors = array(
		'#f0f' => 'aqua', 'black' => '#000', 'fuchsia' => '#f0f', '#808080' => 'grey',
		'#008000' => 'green', '#800000' => 'maroon', '#000080' => 'navy', '#808000' => 'olive',
		'#800080' => 'purple', '#f00' => 'red', '#c0c0c0' => 'silver', '#008080' => 'teal',
		'white' => '#fff', 'yellow' => '#ff0'
	);
	static $config;
	public $selector;
	public $css;
	public $parent_node;
	public $rules = array();
	public $children = array();
	
	function __construct($selector, $css='', $parent_node=null) {
		$this->selector = trim($selector);
		$this->css = trim($css);
		$this->parent_node = $parent_node;
	}
	
	static function extract_importance($values) {
		$important = '';
		
		foreach( $values as $rule => $value ) {
			if( preg_match('/^(."+?) !important$/', $value, $m) ) {
				$important = ' !important';
				$values[$rule] = $m[1];
			}
		}
		
		return array($values, $important);
	}
	
	function replace_shorthands() {
		$rules = array();
		
		// Put sub-selectors in arrays
		$pattern = '/^('.implode('|', array_keys(self::$shorthands)).')-([\w-]+)/';
		
		foreach( $this->rules as $rule => $value ) {
			if( preg_match($pattern, $rule, $m) ) {
				$base_rule = $m[1];
				
				if( isset($rules[$base_rule]) ) {
					if( !is_array($rules[$base_rule]) )
						$rules[$base_rule] = array('__main__' => $rules[$base_rule]);
				} else {
					$rules[$base_rule] = array();
				}
				
				$rules[$base_rule][$m[2]] = $value;
			} else {
				$rules[$rule] = $value;
			}
		}
		
		// Filter out base rules with one property value
		foreach( $rules as $rule => $values ) {
			if( is_array($values) && count($values) == 1 ) {
				$rules[$rule.'-'.key($values)] = reset($values);
				unset($rules[$rule]);
			}
		}
		
		foreach( $rules as $rule => $values ) {
			if( is_array($values) ) {
				list($values, $important) = self::extract_importance($values);
				
				if( $rule == 'font' && isset($rules['line-height']) ) {
					$values['line-height'] = $rules['line-height'];
					unset($rules['line-height']);
				}
				
				if( isset(self::$shorthands[$rule]) ) {
					$replace = true;
					$replacement = '';
					$parts = explode(' ', self::$shorthands[$rule]);
					
					foreach( array_keys($parts) as $i ) {
						$part = $parts[$i];
						
						if( $part == '[' ) {
							$parts[$i + 1] = '[ '.$parts[$i + 1];
							continue;
						}
						
						if( preg_match('%^\[(/)?([^]]+)\]$%', $part, $m) ) {
							$part = $m[2];
							
							if( isset($values[$part]) ) {
								$value = $values[$part];
								
								if( self::$config['compress_colors'] && strpos($part, 'color') !== false )
									$value = self::compress_color($value);
								
								$replacement .= (!strlen($m[1]) ? ' ' : $m[1]).$value;
							}
						} elseif( isset($values[$part]) ) {
							$value = $values[$part];
							
							if( self::$config['compress_colors'] && strpos($part, 'color') !== false )
								$value = self::compress_color($value);
							
							$i && $replacement .= ' ';
							$replacement .= $value;
						} else {
							$replace = false;
							break;
						}
					}
					
					$replace && $values['__main__'] = $replacement;
				}
				
				if( isset($values['__main__']) ) {
					$rules[$rule] = $values['__main__'];
				} else {
					foreach( $values as $sub_rule => $value )
						$rules[$rule.'-'.$sub_rule] = $value;
					
					unset($rules[$rule]);
				}
			}
		}
		
		return $rules;
	}
	
	function compress_color($color) {
		$color = preg_replace('/#(\w)\1(\w)\2(\w)\3/', '#\1\2\3', strtolower($color));
		
		if( isset(self::$colors[$color]) )
			$color = self::$colors[$color];
		
		return $color;
	}
	
	function compress($value, $rule) {
		// Double rule names
		if( preg_match('/^([^#]+)#\d+$/', $rule, $m) )
			$rule = $m[1];
		
		// Compress colors
		if( self::$config['compress_colors'] && preg_match('/color$/', $rule) )
			$value = self::compress_color($value);
		
		// Compress measurements
		if( self::$config['compress_measurements']
				&& ($rule == 'margin' || $rule == 'padding') ) {
			if( preg_match('/^0\w+$/', $value, $m) ) {
				// Replace zero with unit by just zero
				$value = 0;
			} elseif( preg_match('/^(\w+) (\w+) (\w+)(?: (\w+))?$/', $value, $m) ) {
				// Replace redundant margins and paddings
				$value = $m[1].' '.$m[2];
				$left_needed = isset($m[4]) && $m[4] != $m[2];
				$bottom_needed = $left_needed || $m[3] != $m[1];
				
				if( $bottom_needed ) {
					$value .= ' '.$m[3];
					$left_needed && $value .= ' '.$m[4];
				}
			}
		}
		
		return $rule.(self::$config['minify'] ? ':' : ': ').trim($value);
	}
	
	function parse_rules($rules) {
		foreach( preg_split('/\s*;\s*/', trim($rules)) as $rule ) {
			$split = preg_split('/\s*:\s*/', $rule, 2);
			
			if( count($split) == 2 && strlen($split[0]) && strlen($split[1]) ) {
				list($name, $value) = $split;
				$i = 1;
				
				// Double rule names
				while( isset($this->rules[$name]) )
					$name .= '#'.($i++);
				
				$this->rules[$name] = $value;
			}
		}
	}
	
	function parse() {
		$minify = self::$config['minify'];
		$current_node = $this;
		
		// Remove comments and redundant whitespaces
		$css = preg_replace(array('/\s+/', '%/\*.*?\*/%'), array(' ', ''), $this->css);
		
		foreach( array_map('trim', preg_split('/;|\}/', $css)) as $line ) {
			if( preg_match('/^ ?([^{]+) ?\{ ?(.*)$/', $line, $m) ) {
				// Start tag
				$self = preg_match('/^self(.*)/', $m[1], $selector_match);
				$child_selectors = $self ? $selector_match[1] : $m[1];
				
				if( strpos($child_selectors, ',') !== false ) {
					$selectors = array();
					
					foreach( preg_split('/ ?, ?/', trim($child_selectors)) as $child_selector )
						$selectors[] = $current_node->selector.' '.$child_selector;
					
					$selector = implode(',', $selectors);
				} else {
					$selector = $current_node->selector.($self ? '' : ' ').$child_selectors;
				}
				
				$current_node = $current_node->children[] = new CssNode($selector, '', $current_node);
				$line = $m[2];
			}
			
			if( strlen($line) ) {
				// Normal rule
				$current_node->parse_rules($line);
			} else {
				// End tag
				$current_node = $current_node->parent_node;
			}
		}
		
		// Build new CSS string according to config
		$css = '';
		
		if( strlen($this->selector) ) {
			$rules = self::$config['replace_shorthands'] ? $this->replace_shorthands() : $this->rules;
			self::$config['sort_rules'] && ksort($rules);
			$rules = array_map('self::compress', $rules, array_keys($rules));
			
			if( $minify )
				$css .= $this->selector.'{'.implode(';', $rules).'}';
			else
				$css .= preg_replace('/, ?/', ",\n", $this->selector)." {\n\t".implode(";\n\t", $rules).";\n}";
		}
		
		// Parse children recursively
		foreach( $this->children as $child ) {
			$minify || $css .= "\n\n";
			$css .= $child->parse();
		}
		
		return trim($css);
	}
}

class CssParser {
	static $default_config = array(
		'replace_shorthands' => true,
		'sort_rules' => true,
		'minify' => true,
		'compress_measurements' => true,
		'compress_colors' => true
	);
	
	static function minify($css, $config=array()) {
		CssNode::$config = array_merge(self::$default_config, $config);
		$node = new CssNode('', $css);
		
		return $node->parse();
	}
}

?>