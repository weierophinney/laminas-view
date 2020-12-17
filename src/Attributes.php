<?php

/**
 * @see       https://github.com/laminas/laminas-view for the canonical source repository
 * @copyright https://github.com/laminas/laminas-view/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-view/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\View;

use ArrayObject;
use Laminas\Escaper\Escaper;
use Traversable;

/**
 * Class for storing and processing HTML tag attributes.
 */
class Attributes extends ArrayObject
{
    /**
     * HTML escaper
     *
     * @var Escaper
     */
    protected $htmlEscaper;

    /**
     * HTML attribute escaper
     *
     * @var Escaper
     */
    protected $htmlAttrEscaper;

    /**
     * Constructor.
     *
     * @param Escaper $htmlEscaper HTML escaper
     * @param Escaper $htmlAttrEscaper Attribute escaper
     * @param array|Traversable $attribs Attributes
     */
    public function __construct($htmlEscaper, $htmlAttrEscaper, $attribs = [])
    {
        parent::__construct();
        $this->htmlEscaper = $htmlEscaper;
        $this->htmlAttrEscaper = $htmlAttrEscaper;
        foreach ($attribs as $name => $value) {
            $this->offsetSet($name, $value);
        }
    }

    /**
     * Add a value to an attribute.
     *
     * Sets the attribute if it does not exist.
     *
     * @param $name string Name
     * @param $value string|array Value
     *
     * @return Attributes
     */
    public function add($name, $value)
    {
        $this->offsetSet(
            $name,
            $this->offsetExists($name)
                ? array_merge((array) $this->offsetGet($name), (array) $value)
                : $value
        );
        return $this;
    }

    /**
     * Merge attributes with existing attributes.
     *
     * @param $attribs array|Traversable Attributes
     *
     * @return $this
     */
    public function merge($attribs)
    {
        foreach ($attribs as $name => $value) {
            $this->add($name, $value);
        }
        return $this;
    }

    /**
     * Does a specific attribute with a specific value exist?
     *
     * @param $name string Name
     * @param $value string Value
     *
     * @return bool
     */
    public function hasValue($name, $value)
    {
        if (! $this->offsetExists($name)) {
            return false;
        }
        
        $storeValue = $this->offsetGet($name);
        if (is_array($storeValue)) {
            return in_array($value, $storeValue);
        }
        
        return $value === $storeValue;
    }

    /**
     * Return a string of tag attributes.
     *
     * @return string
     */
    public function __toString()
    {
        $xhtml = '';

        foreach ($this->getArrayCopy() as $key => $val) {
            $key = $this->htmlEscaper->escapeHtml($key);

            if ((0 === strpos($key, 'on') || ('constraints' === $key)) && ! is_scalar($val)) {
                // Don't escape event attributes; _do_ substitute double quotes with singles
                // non-scalar data should be cast to JSON first
                $val = json_encode($val, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            }

            if (0 !== strpos($key, 'on') && 'constraints' !== $key && is_array($val)) {
                // Non-event keys and non-constraints keys with array values
                // should have values separated by whitespace
                $val = implode(' ', $val);
            }

            $val    = $this->htmlAttrEscaper->escapeHtmlAttr($val);
            $quote  = strpos($val, '"') !== false ? "'" : '"';
            $xhtml .= sprintf(' %2$s=%1$s%3$s%1$s', $quote, $key, $val);
        }

        return $xhtml;
    }
}
