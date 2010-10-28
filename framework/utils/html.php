<?php
// $div = Tag::factory('div.main#main');
// $a = Tag::factory('a.button.with_icon[href="/" target="_blank"]')->append('Index');
// $div->append($a);
// $div->appendTag('a.other[href="javascript:void(0);"]')->append('Other');
// echo $div;
//
// <div class="main" id="main">
//     <a class="button with_icon" href="/" target="_blank">Index</a>
//     <a class="other" href="javascript:void(0);">Other</a>
// </div>
namespace Lysine\Utils\Html;

use Lysine\Error;

class Tag {
    protected $tag;
    protected $attributes = array();
    protected $children = array();

    public function __construct($tag, array $attributes = array()) {
        $this->tag = $tag;
        $this->attributes = $attributes;
    }

    public function append($child) {
        $this->children[] = $child;
        return $this;
    }

    public function appendTag($tag) {
        if ( !($tag instanceof Tag) )
            $tag = self::factory($tag);

        $this->children[] = $tag;
        return $tag;
    }

    public function setClass($class) {
        $this->attributes['class'] = $class;
        return $this;
    }

    public function setAttribute($name, $val) {
        $this->attributes[$name] = $val;
        return $this;
    }

    public function __toString() {
        $attributes = $this->attributes;
        if (isset($attributes['class']) && is_array($attributes['class']))
            $attributes['class'] = implode(' ', $attributes['class']);

        $attr = array();
        foreach ($attributes as $name => $val) $attr[] = "{$name}=\"{$val}\"";
        $attr = $attr ? ' '. implode(' ', $attr) : '';

        $children = $this->children ? implode('', $this->children) : '';

        $tag = $this->tag;
        return sprintf('<%s%s>%s</%s>', $tag, $attr, $children, $tag);
    }

    static public function parse($tag) {
        if (!preg_match('/^(\w+)([\.\-#\w]+)?(\[.+\])?$/i', $tag, $match))
            throw new Error('Invalid tag syntax');

        $tag = $match[1];
        $class_id = isset($match[2]) ? $match[2] : '';
        $attributes = isset($match[3]) ? $match[3] : array();

        if ($attributes
         && preg_match_all('/([\w\-_]+=[\'"]?[^\s]+[\'"]?)/', trim($attributes, '[]'), $match)) {
            $attributes = array();
            foreach ($match[1] as $attr) {
                list($attr, $val) = explode('=', $attr);
                $attributes[$attr] = trim($val, '"');
            }
        }

        if ($class_id) {
            $class_id = $class_id .'.';
            $class = array();
            $id = $name = $type = '';
            for ($i = 0; $s = substr($class_id, $i, 1); $i++) {
                if ($s == '.' or $s == '#') {
                    if ($type == 'class') {
                        $class[] = $name;
                    } elseif ($type == 'id') {
                        $id = $name;
                    }

                    $type = ($s == '.') ? 'class' : 'id';
                    $name = '';
                } else {
                    $name .= $s;
                }
            }

            if ($class) $attributes['class'] = $class;
            if ($id) $attributes['id'] = $id;
        }

        return array($tag, $attributes);
    }

    static public function factory($tag) {
        list($tag, $attributes) = self::parse($tag);
        return new static($tag, $attributes);
    }
}
