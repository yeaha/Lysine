<?php
class Ly_Db_Statement extends PDOStatement {
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
        return $this->fetch(PDO::FETCH_COLUMN, $col_number);
    }

    /**
     * 所有行某列的结果
     *
     * @param int $col_number
     * @access public
     * @return array
     */
    public function getCols($col_number = 0) {
        return $this->fetchAll(PDO::FETCH_COLUMN, $col_number);
    }

    /**
     * 所有的行，以指定的column name为key
     *
     * @param string $col
     * @access public
     * @return array
     */
    public function getAll($col = null) {
        $rowset = array();
        while ($row = $this->fetch()) {
            if ($col AND array_key_exists($col, $row)) {
                $rowset[$row[$col]] = $row;
            } else {
                $rowset[] = $row;
            }
        }
        return $rowset;
    }
}
