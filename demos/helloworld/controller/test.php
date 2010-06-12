<?php
class Controller_Test {
    public function get() {
        $dbh = db();
        dump($dbh->listConstraints('test.user_roles'));
        //dump($dbh->listIndexes('test.user_roles'));
    }
}
