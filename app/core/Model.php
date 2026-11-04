<?php
/** Model cơ sở: cung cấp kết nối PDO cho các Model con (Product, User, Order...). */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }
}
