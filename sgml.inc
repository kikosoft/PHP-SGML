<?php

/*********************************************************************************
 **                                                                             **
 **   Standard Generalized Markup Language generator                            **
 **                                                                             **
 **       version 1.0    date 22-feb-2013                                       **
 **       version 2.0    date 30-oct-2018                                       **
 **                                                                             **
 **   This class defines how tag-valid SGML code can be generated from PHP.     **
 **   It is specifically, and only, made for generating SGML quickly.           **
 **                                                                             **
 *********************************************************************************/

interface interface_SGML
{
  public function processArguments($arguments);
  public function __construct($name,$arguments = NULL,$isVoid = FALSE);
  public function minimize();
  public function block();
  public function unblock();
  public function write($content);
  public function attach($element);
  public function attributes($attributes);
  public function attribute($name,$value,$append = FALSE);
  public function comment($comment);
  public function getMarkup($minimize = TRUE,$indentLevel = 0);
  public function flush($minimize = TRUE,$handle = NULL);
}

/***************************************************
 **  CONSTRUCTION                                 **
 ***************************************************/

class SGML implements interface_SGML
// an sgml element typically consists of three parts: a start tag, content, and an end tag.
{

  private $isVoid       = FALSE;  // elements without a closing tag, like: <br>
  private $minimize     = FALSE;  // markup of element will always be minimized
  private $blocked      = FALSE;  // when blocked you cannot flush the end tag
  private $startFlushed = FALSE;  // indicate that the start tag was flushed
  private $name         = '';     // tag name, if absent no tag is outputted, only content
  private $content      = '';     // is always a string, if present there are no child elements
  private $elements     = [];     // refers to other sgml elements, if present there is no content
  private $attributes   = [];     // key is attribute name, value is attribute value


  public function processArguments($arguments)
  // split arguments into content and attributes
  {
    // start with nothing
    $content    = '';
    $attributes = [];
    // any arguments?
    if (isset($arguments))
    {
      // if it is an array process that
      if (is_array($arguments))
      {
        // these are basically attributes
        $attributes = $arguments;
        // unless it is named 'content'
        if (isset($attributes['content']))
        {
          // extract the content
          $content = $attributes['content'];
          unset($attributes['content']);
        }
      }
      // a non-array is always seen as content
      else $content = $arguments;
    }
    // return an array with content and attributes
    return [$content,$attributes];
  }


  public function __construct($name,$arguments = NULL,$isVoid = FALSE)
  // create an element with a name and optionally some content
  {
    // split arguments into content and attributes
    list($content,$attributes) = $this->processArguments($arguments);
    // the element name is always lower case
    $this->name    = $name;
    $this->content = $content;
    $this->isVoid  = $isVoid;
    // attach attributes
    if (count($attributes) > 0) $this->attributes($attributes);
    // the constructor always returns the object created
  }

/***************************************************
 **  MINIMIZE                                     **
 ***************************************************/

  public function minimize()
  // render the inside of this element minimized
  {
    $this->minimize = TRUE;
    // return for chaining
    return $this;
  }

/***************************************************
 **  BLOCKING                                     **
 ***************************************************/

  public function block()
  // when blocked you cannot flush the end tag
  {
    $this->blocked = TRUE;
    // return for chaining
    return $this;
  }


  public function unblock()
  // when blocked you cannot flush the end tag
  {
    $this->blocked = FALSE;
    // return for chaining
    return $this;
  }

/***************************************************
 **  NAME                                         **
 ***************************************************/

  private function _hasName()
  // does this element have a name?
  {
    return ($this->name != '');
  }

/***************************************************
 **  content                                         **
 ***************************************************/

  private function _hasContent()
  // does this element have content?
  {
    return ($this->content != '');
  }


  public function write($content)
  // add some content to this element
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

/***************************************************
 **  ELEMENTS                                     **
 ***************************************************/

  private function _hasElements()
  // does this element have one or more child elements?
  {
    return count($this->elements) > 0;
  }


  private function _new($name,$content = '',$isVoid = FALSE)
  // create a new element with a name and optionally some content
  {
    // return a new element
    return new SGML($name,$content,$isVoid);
  }


  public function attach($element)
  // add a new element to this one
  {
    // if a content is present we should promote it to an element
    if ($this->_hasContent())
    {
      // create a new element with the current content
      $this->elements[] = new SGML('',$this->content);
      // and clear the content
      $this->content = '';
    }
    // now we can safely add the new element and return it
    return $this->elements[] = $element;
  }


  private function _attachNew($name,$content = '',$isVoid = FALSE)
  // create a new element and attach it to the current
  {
     return $this->attach($this->_new($name,$content,$isVoid));
  }


  public function __call($name,$arguments)
  // this magic function creates a new element with any name, content and attributes
  {
    // split arguments into content and attributes
    $arguments = isset($arguments[0]) ? $arguments[0] : NULL;
    list($content,$attributes) = $this->processArguments($arguments);
    // make new element
    $element = $this->_attachNew($name,$content);
    // attach attributes
    if (count($attributes) > 0) $element->attributes($attributes);
    // return element
    return $element;
  }

/***************************************************
 **  ATTRIBUTES                                   **
 ***************************************************/

  private function _getAttribute($name)
  // returns all one attribute, if it exists, otherwise we return an empty string
  {
    return isset($this->attributes[$name]) ? $this->attributes[$name] : '';
  }


  public function attributes($attributes)
  // set attributes, always supply an associative array of attributes
  {
    foreach ($attributes as $name => $value) $this->attribute($name,$value);
    // return for chaining
    return $this;
  }


  public function attribute($name,$value,$append = FALSE)
  // set an attribute, overwrite it when it exists
  {
    // append value to existing value
    if ($append) $value = trim($this->_getAttribute($name).' '.$value);
    // assign value
    $this->attributes[$name] = $value;
    // return for chaining
    return $this;
  }

/***************************************************
 **  COMMENTS                                     **
 ***************************************************/

  public function comment($comment)
  // add a comment content, a comment is always an element with the name '--'
  {
    $this->_attachNew('--',$comment);
    // return for chaining
    return $this;
  }

/***************************************************
 **  MARKUP                                       **
 ***************************************************/

  private function _startTag()
  // give back the start tag with attributes
  {
    // no start when it has already been flushed
    if ($this->startFlushed) return '';
    // first the name of this element
    $text = $this->name;
    // then the list of attributes
    foreach ($this->attributes as $attribute => $value)
    {
      $text .= ' '.(($value == '') ? $attribute : $attribute.'="'.addslashes($value).'"');
    }
    // and return it as a tag
    return '<'.$text.'>';
  }


  private function _endTag()
  // give back the end tag
  {
    return ($this->isVoid || $this->blocked) ? '' : '</'.$this->name.'>';
  }


  public function getMarkup($minimize = TRUE,$indentLevel = 0)
  // produces the sgml output string
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

/***************************************************
 **  FLUSHING                                     **
 ***************************************************/

  public function flush($minimize = TRUE,$handle = NULL)
  // flush markup to the given file handle
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
