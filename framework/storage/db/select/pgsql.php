<?php
namespace Lysine\Storage\DB\Select;

use Lysine\Storage\DB\Select;

class Pgsql extends Select {
    private $for_update;

    private $for_share;

    public function forUpdate($nowait = false) {
        $this->for_update = $nowait;
        return $this;
    }

    public function forShare($nowait = false) {
        $this->for_share = $nowait;
        return $this;
    }

    public function compile() {
        list($sql, $bind) = parent::compile();

        if ($this->for_update !== null) {
            $sql .= ' FOR UPDATE';
            if ($this->for_update) $sql .= ' NOTWAIT';
        } elseif ($this->for_share !== null) {
            $sql .= ' FOR SHARE';
            if ($this->for_share) $sql .= ' NOTWAIT';
        }

        return array($sql, $bind);
    }
}
