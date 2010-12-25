<?php
namespace Lysine\Storage\DB;

use Lysine\Storage\DB\IResult;

class Result extends \PDOStatement implements IResult {
    /**
     * __toString魔法方法
     *
     * @access public
     * @return string
     */
    public function __toString() {
        return $this->queryString;
    }

    /**
     * 一行
     *
     * @access public
     * @return array
     */
    public function getRow() {
        return $this->fetch();
    }

    /**
     * 第一行某列的结果
     *
     * @param int $col_number
     * @access public
     * @return mixed
     */
    public function getCol($col_number = 0) {
        return $this->fetch(\PDO::FETCH_COLUMN, $col_number);
    }

    /**
     * 所有行某列的结果
     *
     * @param int $col_number
     * @access public
     * @return array
     */
    public function getCols($col_number = 0) {
        return $this->fetchAll(\PDO::FETCH_COLUMN, $col_number);
    }

    /**
     * 所有的行，以指定的column name为key
     *
     * @param string $col
     * @access public
     * @return array
     */
    public function getAll($col = null) {
        if (!$col) return $this->fetchAll();

        $rowset = array();
        while ($row = $this->fetch())
            $rowset[$row[$col]] = $row;
        return $rowset;
    }
}
