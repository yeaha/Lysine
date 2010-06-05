<?php
interface Ly_Db_Adapter_Interface {
    public function exec();
    public function insert();
    public function update();
    public function delete();
}

interface Ly_Cache_Interface {
    public function set();
    public function get();
    public function delete();
}
