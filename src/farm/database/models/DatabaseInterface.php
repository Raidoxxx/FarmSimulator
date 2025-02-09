<?php

namespace farm\database\models;

use mysqli;

interface DatabaseInterface
{
    public function getConnection(): mysqli;
    public function close(): void;
}