<?php
namespace Lysine\Db;

class Expr {
    protected $expr;

    public function __construct($expr) {
        $this->expr = $expr;
    }

    public function __toString() {
        return $this->expr;
    }
}
