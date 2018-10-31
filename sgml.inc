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
  public function __construct($name,$text = '',$isVoid = FALSE);
  public function minimize();
  public function block();
  public function unblock();
  public function write($text);
  public function attach($element);
  public function setAttrs($attributes);
  public function setAttr($name,$value);
  public function appendAttr($name,$value);
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
  private $text         = '';     // is always a string, if present there are no child elements
  private $elements     = [];     // refers to other sgml elements, if present there is no content
  private $attributes   = [];     // key is attribute name, value is attribute value


  public function __construct($name,$text = '',$isVoid = FALSE)
  // create an element with a name and optionally some text
  {
    // the element name is always lower case
    $this->name        = mb_strtolower($name);
    $this->text        = $text;
    $this->isVoid = $isVoid;
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
 **  TEXT                                         **
 ***************************************************/

  private function _hasText()
  // does this element have text?
  {
    return ($this->text != '');
  }


  public function write($text)
  // add some text to this element
  {
    // if elements are present we should add the text as an element
    if ($this->_hasElements()) $this->_attachNew('',$text);
    // otherwise we can add it to the existing text
    elseif ($this->_hasText()) $this->text .= $text;
    // or make it the new text
    else $this->text = $text;
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


  private function _new($name,$text = '',$isVoid = FALSE)
  // create a new element with a name and optionally some text
  {
    // return a new element
    return new SGML($name,$text,$isVoid);
  }


  public function attach($element)
  // add a new element to this one
  {
    // if a text is present we should promote it to an element
    if ($this->_hasText())
    {
      // create a new element with the current text
      $this->elements[] = new SGML('',$this->text);
      // and clear the text
      $this->text = '';
    }
    // now we can safely add the new element and return it
    return $this->elements[] = $element;
  }


  private function _attachNew($name,$text = '',$isVoid = FALSE)
  // create a new element and attach it to the current
  {
     return $this->attach($this->_new($name,$text,$isVoid));
  }


/***************************************************
 **  ATTRIBUTES                                   **
 ***************************************************/

  private function _getAttr($name)
  // returns all one attribute, if it exists, otherwise we return an empty string
  {
    $name = mb_strtolower($name);
    return isset($this->attributes[$name]) ? $this->attributes[$name] : '';
  }


  public function setAttrs($attributes)
  // set attributes, always supply an associative array of attributes
  {
    foreach ($attributes as $name => $value) $this->setAttr($name,$value);
    // return for chaining
    return $this;
  }


  public function setAttr($name,$value)
  // set an attribute, overwrite it when it exists
  {
    $this->attributes[mb_strtolower($name)] = $value;
    // return for chaining
    return $this;
  }


  public function appendAttr($name,$value)
  // append an attribute, appent it when it exists
  {
    $name = mb_strtolower($name);
    $this->attributes[$name] = trim($this->_getAttr($name).' '.$value);
    // return for chaining
    return $this;
  }

/***************************************************
 **  COMMENTS                                     **
 ***************************************************/

  public function comment($comment)
  // add a comment text, a comment is always an element with the name '--'
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
    $content = $this->name;
    // then the list of attributes
    foreach ($this->attributes as $attribute => $value)
    {
      $content .= ' '.(($value == '') ? $attribute : $attribute.'="'.addslashes($value).'"');
    }
    // and return it as a tag
    return '<'.$content.'>';
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
    // any element with a text should be concatenated
    if ($this->_hasText())
    {
      // does it have an element name?
      if ($this->_hasName())
      {
        // this could be a comment, otherwise it is a normal tag
        if ($this->name == '--') $sgml = $innerMinimize ? '' : '<!-- '.$this->text.' -->';
                            else $sgml = $this->_startTag().$this->text.$this->_endTag();
      }
      else $sgml = $this->text; // it's just a text
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
    else $sgml = ''; // no name, no elements
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
    $this->text       = '';
    $this->elements   = [];
    $this->attributes = [];
  }

}