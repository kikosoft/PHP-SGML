<?php

/**
 * SGML - PHP SGML composer class.
 * In SGML each element has a start tag, content, and an end tag.
 * A start tag can have several attributes. Each attribute has a name and value.
 *
 * @see       https://github.com/kikosoft/SGML The SGML GitHub project
 *
 * @author    Peter de Jong <dejong@kikosoft.com>
 * @copyright 2018 Peter de Jong
 * @license   https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 *            ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *            FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Low level SGML class.
 */
class SGML
{
  /** @var string $voidClosure Detemine how a void element is closed. */
  private $voidClosure = '';

  /** @var bool $isVoid Whether this element is without a closing tag, like: <br>. */
  private $isVoid = FALSE;

  /** @var bool $humanReadable This code cannot be rendered minimized. */
  private $humanReadable = FALSE;

  /** @var bool $locked Whether this element is locked so you cannot flush the end tag. */
  private $locked = FALSE;

  /** @var bool $startFlushed Whether the start tag of this element was flushed. */
  private $startFlushed = FALSE;

  /** @var bool $endFlushed Whether the end tag of this element was flushed. */
  private $endFlushed = FALSE;

  /** @var object|null $parent Parent element of this element. */
  private $parent = NULL;

  /** @var string $name The tag name of this element. */
  private $name = '';

  /** @var string $content The content of this element, apart from child elements. */
  private $content = '';

  /** @var array $elements The child elements of this element. */
  private $elements = [];

  /** @var array $attributes Attributes of this element. */
  private $attributes = [];

  /** @var bool $flushingBlocked Flag to indicate that a lock was encountered. */
  protected static $blocked = FALSE;

  /**
   * Split arguments into content and attributes.
   *
   * Example 1: 'my content'
   * Example 2: ['size' => 5, 'title' => 'test]
   * Example 3: ['my content',['size' => 5, 'title' => 'test]]
   * Example 4: [['size' => 5, 'title' => 'test],'my content']
   * Example 5: [['size' => 5],'my content',['title' => 'test],'more content']
   *
   * @param array|string|null $arguments Arguments supplied to a SGML element
   * @return array|null Array containing content and attributes, or NULL.
   */
  public static function processArguments($arguments)
  {
    // start with no content or attributes
    $content    = [];
    $attributes = [];
    // any arguments?
    if (isset($arguments))
    {
      if (is_array($arguments))
      foreach ($arguments as $key => $argument)
      {
        // an array is always seens as attributes
        if (is_array($argument))
        foreach ($argument as $name => $value)
        {
          // check for valueless attributes
          if (is_numeric($name)) $attributes[$value] = '';
                            else $attributes[$name] = $value;
        }
        else
        {
          // if the key is numeric it has to be content
          if (is_numeric($key)) $content[] = $argument;
          // otherwize we see it as an attribute
          else $attribute[$key] = $argument;
        }
      }
      // a non-array is always seen as content
      else $content[] = $arguments;
    }
    return [implode(' ',$content),$attributes];
  }

  /**
   * Create an element with a name and optionally some content.
   *
   * @param object|null $parent The parent element of this element, or null.
   * @param string $name Name of the new element.
   * @param array|string|null $arguments Arguments of the element.
   */
  public function __construct($parent = NULL,$name = '',$arguments = NULL)
  {
    // split arguments into content and attributes
    list($content,$attributes) = self::processArguments($arguments);
    // store what we known about this element
    $this->parent     = $parent;
    $this->name       = $name;
    $this->content    = $content;
    $this->attributes = $attributes;
    // attach this element to the parent
    if (isset($parent)) $parent->attach($this);
  }

  /**
   * Render the inside of this element in human readable format.
   *
   * @return object
   */
  public function humanReadable()
  {
    $this->humanReadable = TRUE;
    // return for chaining
    return $this;
  }

  /**
   * This is a void, or self closing, element.
   * SGML doesn't really have void elements, but HTML and XML do.
   *
   * @param string $closure Use either ' /' for XML or '' for HTML (default).
   * @return object
   */
  public function void($closure = '')
  {
    $this->voidClosure = $closure;
    $this->isVoid      = TRUE;
    // return for chaining
    return $this;
  }

  /**
   * This is not a void, or self closing, element.
   * SGML doesn't really have void elements, but HTML and XML do.
   *
   * @return object
   */
  public function unvoid()
  {
    $this->isVoid = FALSE;
    // return for chaining
    return $this;
  }

  /**
   * When locked you cannot flush the end tag.
   *
   * @return object
   */
  public function lock()
  {
    $this->locked = TRUE;
    // return for chaining
    return $this;
  }

  /**
   * When locked you cannot flush the end tag.
   *
   * @return object
   */
  public function unlock()
  {
    $this->locked = FALSE;
    // return for chaining
    return $this;
  }

  /**
   * Set the name of this element.
   *
   * @return object
   */
  public function setName($name)
  {
    $this->name = $name;
     return $this;
  }

  /**
   * Read the content of this element.
   *
   * @return string
   */
  public function read()
  {
    return $this->content;
  }

  /**
   * Add some content to this element.
   * @param string $content Add content to the sgml element
   *
   * @return object
   */
  public function write($content)
  {
    // if elements are present we should add the content as an new element
    if ($this->hasElements()) new SGML($this,'',$content);
    // otherwise we can add it to the content
    else $this->content .= $content;
    // return for chaining
    return $this;
  }

  /**
   * Does this element have one or more child elements?
   *
   * @return bool
   */
  public function hasElements()
  {
    return count($this->elements) > 0;
  }

  /**
   * Attach a new element to this one.
   *
   * @param object $element
   * @return object
   */
  public function attach($element)
  {
    $this->elements[] = $element;
    return $this;
  }

  /**
   * This magic function creates a new element with any name, content and attributes.
   * The usage of __call is controversial but is needed here to reduce code bloat.
   *
   * @param string $name
   * @param array|string|null $arguments
   * @return object
   */
  public function __call($name,$arguments)
  {
    // make new sgml element
    return new SGML($this,$name,$arguments);
  }

  /**
   * Returns the parent of this element or null it no parent exists.
   *
   * @return object|null
   */
  public function parent()
  {
    return $this->parent;
  }

  /**
   * Returns true, if attribute exists, otherwise returns false.
   *
   * @param string $name
   * @return bool
   */
  public function hasAttribute($name)
  {
    return isset($this->attributes[$name]);
  }

  /**
   * Returns one attribute, if it exists, otherwise we return an empty string.
   *
   * @param string $name
   * @return string
   */
  public function getAttribute($name)
  {
    return isset($this->attributes[$name]) ? $this->attributes[$name] : '';
  }

  /**
   * Set attributes, always supply an associative array of attributes.
   *
   * @param array|null $attributes
   * @return object
   */
  public function setAttributes($attributes)
  {
    $this->attributes += $attributes;
    return $this;
  }

  /**
   * Set an attribute, overwrite it when it exists or append.
   *
   * @param string $name
   * @param string $value
   * @param bool $append
   * @return object
   */
  public function setAttribute($name,$value,$append = FALSE)
  {
    // append value to existing value
    if ($append) $value = trim($this->getAttribute($name).' '.$value);
    // assign value
    $this->attributes[$name] = $value;
    // return for chaining
    return $this;
  }

  /**
   * removes a single attribute from the attribures array.
   *
   * @param string $name Which attribute to remove.
   * @return string|null
   */
  protected function removeAttribute($name)
  {
    unset($this->attributes[$name]);
    return $this;
  }

  /**
   * removes a single attribute from the attribures array and returns it.
   *
   * @param string $name Which attribute to extract.
   * @param string|null $default Default value when absent.
   * @param array|null $options Possible values. Leave empty for any value.
   * @return string|null
   */
  protected function extractAttribute($name,$default = NULL,$options = NULL)
  {
    // begin value
    $value = $default;
    // get proposal, or not
    if (isset($this->attributes[$name]))
    {
      $proposal = $this->attributes[$name];
      if (is_array($options)) $value = in_array($proposal,$options) ? $proposal : $value;
                         else $value = $proposal;
      unset($this->attributes[$name]);
    }
    return $value;
  }

  /**
   * Add a comment content, a comment is always an element with the name '--'.
   *
   * @param string $comment
   * @return object
   */
  public function comment($comment)
  {
    new SGML($this,'--',$comment);
    // return for chaining
    return $this;
  }

  /**
   * Returns the start tag with attributes.
   *
   * @return string
   */
  private function _startTag()
  {
    // first the name of this element
    $text = $this->name;
    // then the list of attributes
    foreach ($this->attributes as $attribute => $value)
    {
      // if the attribute is numeric it is a boolean attributes
      if (is_numeric($attribute))
      {
        $text .= ' '.addslashes($value);
      }
      else $text .= ' '.(($value == '') ? $attribute : $attribute.'="'.addslashes($value).'"');
    }
    // the start was flushed
    $this->startFlushed = TRUE;
    // and return it as a tag, also sets correct closure
    return '<'.$text.($this->isVoid ? $this->voidClosure : '').'>';
  }

  /**
   * Returns the end tag.
   *
   * @return string
   */
  private function _endTag()
  {
    // delete content and child elements after flushing
    $this->content  = '';
    $this->elements = [];
    // if this tag locked we set the blocked flag
    if ($this->locked)
    {
      self::$blocked = TRUE;
      return '';
    }
    // the end was flushed
    $this->endFlushed = TRUE;
    // return
    return $this->isVoid ? '' : '</'.$this->name.'>';
  }

  /**
   * Produces the sgml output string.
   *
   * @param bool $minimize
   * @param int $indentLevel
   * @return string
   */
  public function getMarkup($minimize = TRUE,$indentLevel = 0)
  {
    // if it's only content we simple return it
    if ($this->name == '') return $this->content;
    // adjust minimizing to force human readability?
    $minimize = $minimize && !$this->humanReadable;
    // the normal indent string for this element
    $indent = $minimize ? '' : str_repeat('  ',$indentLevel);
    // the normal ending for this element
    $eol = $minimize ? '' : PHP_EOL;
    // any element with elements should not be concatenated
    if ($this->hasElements())
    {
      // get start tag
      $sgml = $this->startFlushed ? '' : $indent.$this->_startTag().$eol;
      // increase indent level
      if (!$minimize) $indentLevel++;
      // get the elements
      foreach ($this->elements as $element)
      {
        $sgml .= $element->getMarkup($minimize,$indentLevel);
        // break on a block by a lock
        if (self::$blocked) break;
      }
      // get end tag, when not blocked
      if (!($this->endFlushed || self::$blocked))
      {
        // and end tag can cause a blockage, so we do call it before...
        $endTag = $this->_endTag();
        // it is actually being rendered
        if (!self::$blocked) $sgml .= $indent.$endTag.$eol;
      }
    }
    // any other element should be concatenated
    else
    {
      // this could be a comment
      if ($this->name == '--')
      {
        // no comments in minimized code
        $sgml = $minimize ? '' : $indent.'<!-- '.$this->content.' -->'.PHP_EOL;
      }
      else
      {
        // a normal tag with no child elements
        $sgml = $this->startFlushed ? '' : $indent.$this->_startTag().$this->content;
        // end tag, when not already flushed
        if (!$this->endFlushed)
        {
          $endTag = $this->_endTag();
          $sgml  .= $endTag.($minimize || self::$blocked ? '' : PHP_EOL);
        }
      }
    }
    // return sgml
    return $sgml;
  }

  /**
   * Flush markup to the given file handle.
   *
   * @param bool $minimize
   * @param object|null $handle
   */
  public function flush($minimize = TRUE,$handle = NULL)
  {
    // we start not being blocked by a lock
    self::$blocked = FALSE;
    // get the markup
    $markup = $this->getMarkup($minimize);
    // either echo markup or write it to file
    if (is_null($handle)) return $markup;
                     else return fwrite($handle,$markup);
    // cleanup
    $this->content    = '';
    $this->elements   = [];
    $this->attributes = [];
  }

}
