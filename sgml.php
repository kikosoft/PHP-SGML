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

namespace html5php\html5;

/**
 * Low level SGML class.
 */
class SGML
{
  /**
   * Detemine how a void element is closed.
   * Use '' for html, and ' /' for xml.
   *
   * @var string
   */
  private $voidClosure  = '';

  /**
   * Whether this element is without a closing tag, like: <br>.
   *
   * @var bool
   */
  private $isVoid       = FALSE;

  /**
   * Whether the markup of this element will be minimized.
   *
   * @var bool
   */
  private $minimize     = FALSE;

  /**
   * Whether this element is locked so you cannot flush the end tag.
   *
   * @var bool
   */
  private $blocked      = FALSE;

  /**
   * Whether the start tag of this element was flushed.
   *
   * @var bool
   */
  private $startFlushed = FALSE;

  /**
   * The tag name of this element.
   * If absent no start or end tag, only content, is send to the output.
   *
   * @var string
   */
  private $name         = '';

  /**
   * The content of this element, apart from child elements.
   * If present there are no child elements.
   *
   * @var string
   */
  private $content      = '';

  /**
   * The child elements of this element.
   * If present there is no content.
   *
   * @var array
   */
  private $elements     = [];

  /**
   * Attributes of this element.
   * The array keys are attribute names, and array values are attribute values.
   *
   * @var array
   */
  private $attributes   = [];

  /**
   * Split arguments into content and attributes.
   *
   * Example 1: 'my content'
   * Example 2: ['size' => 5, 'title' => 'test]
   * Example 3: ['my content',['size' => 5, 'title' => 'test]]
   * Example 4: [['size' => 5, 'title' => 'test],'my content']
   * Example 5: [['size' => 5],'my content',['title' => 'test],'more content']
   *
   * @param array $arguments Arguments supplied to a SGML element
   * @return array|null Array containing content and attributes, or NULL.
   */
  public function processArguments($arguments)
  {
    // start with no content or attributes
    $content    = '';
    $attributes = [];
    // any arguments?
    if (isset($arguments))
    {
      if (is_array($arguments))
      foreach ($arguments as $key => $value)
      {
        // an array is always seens as attributes
        if (is_array($value)) $attributes += $value;
        else
        {
          // if the key is numeric it has to be content
          if (is_numeric($key)) $content .= $value;
          // otherwize we see it as an attribute
                         else $this->attribute($key,$value);
        }
      }
      // a non-array is always seen as content
      else $content = $arguments;
    }
    return [$content,$attributes];
  }

  /**
   * Create an element with a name and optionally some content.
   *
   * @param string $name
   * @param array|null $arguments
   */
  public function __construct($name,$arguments = NULL)
  {
    // split arguments into content and attributes
    list($content,$attributes) = $this->processArguments($arguments);
    // store
    $this->name    = $name;
    $this->content = $content;
    // attach attributes
    if (count($attributes) > 0) $this->attributes($attributes);
  }

  /**
   * Render the inside of this element minimized.
   *
   * @return object
   */
  public function minimize()
  {
    $this->minimize = TRUE;
    // return for chaining
    return $this;
  }

  /**
   * This is a void, or self closing, element.
   * SGML doesn't really have void elements, but html and xml do.
   *
   * @param string $closure Use either ' /' or '', the latter is the default.
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
   * When blocked you cannot flush the end tag.
   *
   * @return object
   */
  public function block()
  {
    $this->blocked = TRUE;
    // return for chaining
    return $this;
  }

  /**
   * When blocked you cannot flush the end tag.
   *
   * @return object
   */
  public function unblock()
  {
    $this->blocked = FALSE;
    // return for chaining
    return $this;
  }

  /**
   * Does this element have a name?
   *
   * @return bool
   */
  private function _hasName()
  {
    return ($this->name != '');
  }

  /**
   * Does this element have content?
   *
   * @return bool
   */
  private function _hasContent()
  {
    return ($this->content != '');
  }

  /**
   * Add some content to this element.
   * @param string $content Add content to the sgml element
   *
   * @return object
   */
  public function write($content)
  {
    // if elements are present we should add the content as an element
    if ($this->_hasElements()) $this->_attachNew('',$content);
    // otherwise we can add it to the existing content
    elseif ($this->_hasContent()) $this->content .= $content;
    // or make it the new content
    else $this->content = $content;
    // return for chaining
    return $this;
  }

  /**
   * Does this element have one or more child elements?
   *
   * @return bool
   */
  private function _hasElements()
  {
    return count($this->elements) > 0;
  }

  /**
   * Create a new element with a name and optionally some content.
   *
   * @param string $name
   * @param array|null $arguments
   * @return object
   */
  private function _new($name,$arguments = NULL)
  {
    // return a new element
    return new SGML($name,$arguments);
  }

  /**
   * Add a new element to this one.
   *
   * @param object $element
   * @return object
   */
  public function attach($element)
  {
    // if a content is present we should promote it to an element
    if ($this->_hasContent())
    {
      // create a new element with the current content
      $this->elements[] = $this->_new('',$this->content);
      // and clear the content
      $this->content = '';
    }
    // now we can safely add the new element and return it
    return $this->elements[] = $element;
  }

  /**
   * Ccreate a new element and attach it to the current.
   *
   * @param string $name
   * @param array|null $arguments
   * @return object
   */
  private function _attachNew($name,$arguments = NULL)
  {
     return $this->attach($this->_new($name,$arguments));
  }

  /**
   * This magic function creates a new element with any name, content and attributes.
   *
   * @param string $name
   * @param array|null $arguments
   * @return object
   */
  public function __call($name,$arguments)
  {
    // make new element
    return $this->_attachNew($name,$arguments);
  }

  /**
   * Returns all one attribute, if it exists, otherwise we return an empty string.
   *
   * @param string $name
   * @return string
   */
  private function _getAttribute($name)
  {
    return isset($this->attributes[$name]) ? $this->attributes[$name] : '';
  }

  /**
   * Set attributes, always supply an associative array of attributes.
   *
   * @param array|null $attributes
   * @return object
   */
  public function attributes($attributes)
  {
    foreach ($attributes as $name => $value) $this->attribute($name,$value);
    return $this;
  }

  /**
   * Set an attribute, overwrite it when it exists.
   *
   * @param string $name
   * @param string $value
   * @param bool $append
   * @return object
   */
  public function attribute($name,$value,$append = FALSE)
  {
    // append value to existing value
    if ($append) $value = trim($this->_getAttribute($name).' '.$value);
    // assign value
    $this->attributes[$name] = $value;
    // return for chaining
    return $this;
  }

  /**
   * Add a comment content, a comment is always an element with the name '--'.
   *
   * @param string $comment
   * @return object
   */
  public function comment($comment)
  {
    $this->_attachNew('--',$comment);
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
    // no start when it has already been flushed
    if ($this->startFlushed) return '';
    // first the name of this element
    $text = $this->name;
    // then the list of attributes
    foreach ($this->attributes as $attribute => $value)
    {
      // if the attribute is numeric it is a boolean attributes
      if (is_numeric($attribute)) $text .= ' '.addslashes($value);
      else $text .= ' '.(($value == '') ? $attribute : $attribute.'="'.addslashes($value).'"');
    }
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
    return ($this->isVoid || $this->blocked) ? '' : '</'.$this->name.'>';
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
    // this is the normal indent string for this element
    $indent = str_repeat('  ',$indentLevel);
    // do we minimize the inside of the element?
    $innerMinimize = $this->minimize || $minimize;
    // any element with a content should be concatenated
    if ($this->_hasContent())
    {
      // does it have an element name?
      if ($this->_hasName())
      {
        // this could be a comment, otherwise it is a normal tag
        if ($this->name == '--') $sgml = $innerMinimize ? '' : '<!-- '.$this->content.' -->';
                            else $sgml = $this->_startTag().$this->content.$this->_endTag();
      }
      else $sgml = $this->content; // it's just a content
    }
    // otherwise it could have elements and we need to get those
    elseif ($this->_hasElements())
    {
      // start tag
      $sgml = $this->_hasName() ? $this->_startTag().($innerMinimize ? '' : PHP_EOL) : '';
      // elements
      foreach ($this->elements as $element) $sgml .= $element->getMarkup($innerMinimize,$indentLevel+1);
      // end tag
      $sgml .= ($innerMinimize ? '' : $indent).$this->_endTag();
    }
    // no content and no elements, does it have at least a name?
    elseif ($this->_hasName()) $sgml = $this->_startTag().$this->_endTag();
    // no content, no elements and no name
    else $sgml = '';
    // return sgml
    return $minimize ? $sgml : $indent.$sgml.PHP_EOL;
  }


  /**
   * Flush markup to the given file handle.
   *
   * @param bool $minimize
   * @param object|null $handle
   */
  public function flush($minimize = TRUE,$handle = NULL)
  {
    // get the markup
    $markup = $this->getMarkup($minimize);
    // either echo markup or write it to file
    if (is_null($handle)) echo $markup;
                     else fwrite($handle,$markup);
    // the start was flushed
    $this->startFlushed = TRUE;
    // cleanup
    $this->content    = '';
    $this->elements   = [];
    $this->attributes = [];
  }

}
