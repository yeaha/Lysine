<?php
class Ly_Db_Expr {
    protected $expr;

    public function __construct($expr) {
        $this->expr = $expr;
    }

    public function __toString() {
        return $this->expr;
    }
}
