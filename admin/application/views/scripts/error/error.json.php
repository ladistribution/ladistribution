<?php

$error = array(
    'status'  => $this->status,
    'message' => $this->message,
    'details' => $this->details
);

$this->jsonRenderer($error);
