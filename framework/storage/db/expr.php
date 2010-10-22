<?php
namespace Lysine\Storage\DB;

/**
 * 数据库表达式
 * Expr类型不会被adapter qstr()逃逸处理
 * 可以用于包装那些不希望被adapter逃逸处理的内容
 * 使用时需要自己注意安全方面的考虑
 *
 * @package Storage
 * @author yangyi <yangyi.cn.gz@gmail.com>
 */
class Expr {
    protected $expr;

    public function __construct($expr) {
        if ($expr instanceof Expr) $expr = $expr->__toString();
        $this->expr = $expr;
    }

    public function __toString() {
        return $this->expr;
    }
}
