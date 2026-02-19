<?php
use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('email_messages');
print_r($columns);
