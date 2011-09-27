<?php
namespace Lysine\Utils\DOM;

use Lysine\Error;

if (!extension_loaded('dom'))
    throw Error::require_extension('dom');

class Document extends \DOMDocument {
    /**
     * xpath查询handle
     * 
     * @var DOMXPath
     * @access private
     */
    private $xpath_handle;

    /**
     * 构造函数
     *
     * @param   string  $version
     * @param   string  $encoding
     */
    public function __construct(/* string */$version = '1.0', /* string */$encoding = 'utf-8') {
        parent::__construct($version, $encoding);
        // 把\Lysine\Utils\DOM\Element类注册为默认的Node class
        $this->registerNodeClass('DOMElement', '\Lysine\Utils\DOM\Element');
    }

    /**
     * xpath查询
     *
     * @param   string  $query
     * @param   boolean $return_first
     * @return  mixed
     */
    public function select(/* string */$query, /* boolean */$return_first = false) {
        return $this->documentElement->select($query, $return_first);
    }

    /**
     * 生成当前document的xpath查询handle
     *
     * @return  DOMXPath
     */
    public function xpath() {
        return $this->xpath_handle
            ?: ($this->xpath_handle = new \DOMXPath($this));
    }

    /**
     * 返回当前document的xml字符串内容
     *
     * @return  string
     */
    public function __toString() {
        return $this->saveXML();
    }
}

class Element extends \DOMElement {
    /**
     * 批量设置attribute
     *
     * @param   array $attrs
     * @return  Element
     */
    public function setAttributes(array $attrs) {
        foreach ($attrs as $key => $val)
            $this->setAttribute($key, $val);
        return $this;
    }

    /**
     * 批量获取attribute，如果指定了key则只返回指定的
     *
     * @param   string  $key
     * @return  array
     */
    public function getAttributes(/* string */$key = null/* [, $key2[, $key3[, ...]]] */) {
        $result = array();

        if ($key === null) {
            foreach ($this->attributes as $attr)
                $result[$attr->nodeName] = $attr->nodeValue;
        } else {
            foreach (func_get_args() as $key)
                $result[$key] = $this->getAttribute($key);
        }

        return $reuslt;
    }

    /**
     * xpath查询
     *
     * @param   string  $query
     * @param   boolean $return_first
     */
    public function select(/* string */$query, /* boolean */$return_first = false) {
        if (!$this->ownerDocument)
            throw new Error('Element must have ownerDocument while select()');

        $result = $this->ownerDocument->xpath()->evaluate($query, $this);
        return ($return_first AND $result instanceof \DOMNodelist) ? $result->item(0) : $result;
    }

    /**
     * 插入一个新的子节点到指定的子节点之后，返回插入的新子节点
     *
     * @param   DOMNode $newnode
     * @param   DOMNode $refnode
     *
     * @return  DOMNode
     */
    public function insertAfter(\DOMNode $newnode, \DOMNode $refnode) {
        if ($refnode = $refnode->nextSibling) {
            $this->insertBefore($newnode, $refnode);
        } else {
            $this->appendChild($newnode);
        }
        return $newnode;
    }

    /**
     * 把节点插入到指定节点的指定位置
     *
     * @param   DOMNode $refnode
     * @param   string  $where
     * @return  DOMNode
     */
    public function inject(\DOMNode $refnode, $where = 'bottom') {
        $where = strtolower($where);

        if ('before' == $where) {
            $refnode->parentNode->insertBefore($this, $refnode);
        } elseif ('after' == $where) {
            $refnode->parentNode->insertAfter($this, $refnode);
        } else {
            if ('top' == $where AND $first = $refnode->firstChild) {
                $refnode->insertBefore($this, $first);
            } else {
                $refnode->appendChild($this);
            }
        }

        return $this;
    }

    /**
     * 是否是第一个子节点
     *
     * @return  boolean
     */
    public function isFirst() {
        return $this->previousSibling ? false : true;
    }

    /**
     * 是否最后一个子节点
     *
     * @return  boolean
     */
    public function isLast() {
        return $this->nextSibling ? false : true;
    }

    /**
     * 清除所有的子节点
     *
     * @return DOMNode
     */
    public function clean() {
        foreach ($this->childNodes as $child)
            $this->removeChild($child);
        return $this;
    }

    /**
     * 删除自己
     */
    public function erase() {
        return $this->parentNode->removeChild($this);
    }

    /**
     * 把xml字符串插入到当前节点尾部
     *
     * @param   string  $xml
     * @return  DOMElement
     */
    public function appendXML($xml) {
        if (!$this->ownerDocument)
            throw new Error('Element must have ownerDocument while appendXML()');

        $fragment = $this->ownerDocument->createDocumentFragment();
        $fragment->appendXML($xml);
        return $this->appendChild($fragment);
    }

    /**
     * 用xml字符串替换当前节点的所有子节点
     *
     * @param   string  $xml
     * @return  DOMElement
     */
    public function replaceXML($xml) {
        return $this->clean()->appendXML($xml);
    }

    /**
     * 根据给定的nodeName和attribute数组，对节点进行比较
     * 
     * @param DOMElement $node
     * @param string $node_name
     * @param array $attrs
     * @access protected
     * @return boolean
     */
    protected function _match($node, $node_name, array $attrs = array()) {
        if ($node->nodeName != $node_name) return false;

        foreach ($attrs as $key => $value)
           if ($node->getAttribute($key) != $value) return false;

        return true;
    }

    /**
     * 在同级之前的节点中查找
     * 可以指定nodeName和attributes匹配条件
     * 
     * @param string $node_name 
     * @param array $attrs 
     * @access protected
     * @return mixed
     */
    public function prev($node_name = null, array $attrs = array()) {
        if ($node_name === null)
            return $this->previousSibling;

        $current = $this;
        while ($prev = $current->previousSibling) {
            if ($this->_match($prev, $node_name, $attrs)) return $prev;
            $current = $prev;
        }
        return null;
    }

    /**
     * 在同级之后的节点中查找
     * 可以指定nodeName和attributes匹配条件
     * 
     * @param string $node_name 
     * @param array $attrs 
     * @access public
     * @return mixed
     */
    public function next($node_name = null, array $attrs = array()) {
        if ($node_name === null)
            return $this->nextSibling;

        $current = $this;
        while ($next = $current->nextSibling) {
            if ($this->_match($next, $node_name, $attrs)) return $next;
            $current = $next;
        }
        return null;
    }

    /**
     * 返回当前element的xml字符串，相当于javascript dom里的outerHTML()
     *
     * @return  string
     */
    public function __toString() {
        if ($this->ownerDocument)
            return $this->ownerDocument->saveXML($this);

        $doc = new \DOMDocument();
        $el = $this->cloneNode(true);
        $doc->appendChild($el);
        return $doc->saveXML($el);
    }
}
