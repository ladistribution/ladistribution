<?php

require_once 'Zend/Json.php';

class View_Helper_JsonRenderer
{
    public function jsonRenderer($object, $echo = false)
    {
        $json = Zend_Json::encode($object);

        if ($this->pretty()) {
            if ($echo) header('Content-type: text/plain');
            $content = $this->indent($json);
        } else {
            if ($echo) header('Content-type: application/json');
            $content = $json;
        }
        if ($echo) {
            echo $content;
            return;
        }
        return $content;
    }
    
    public function pretty()
    {
        if (defined('DEBUG') && DEBUG) {
            return true;
        }
        if (isset($_GET['pretty'])) {
            return true;
        }
        if (strpos($_SERVER["HTTP_USER_AGENT"], 'Mozilla') !== false ) {
            if (empty($_SERVER["HTTP_X_REQUESTED_WITH"])) {
                return true;
            }
        }
        return false;
    }

    // function found on http://php.net/json_encode
    public function indent($json)
    {
        $tab = "  ";
        $new_json = "";
        $indent_level = 0;
        $in_string = false;

        $len = strlen($json);

        for ($c = 0; $c < $len; $c++) {
            $char = $json[$c];
            switch ($char) {
                case '{':
                case '[':
                    if (!$in_string) {
                        $new_json .= $char . "\n" . str_repeat($tab, $indent_level+1);
                        $indent_level++;
                    } else {
                        $new_json .= $char;
                    }
                    break;
                case '}':
                case ']':
                    if (!$in_string) {
                        $indent_level--;
                        $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
                    } else {
                        $new_json .= $char;
                    }
                    break;
                case ',':
                    if (!$in_string) {
                        $new_json .= ",\n" . str_repeat($tab, $indent_level);
                    } else {
                        $new_json .= $char;
                    }
                    break;
                case ':':
                    if(!$in_string) {
                        $new_json .= ": ";
                    } else {
                        $new_json .= $char;
                    }
                    break;
                case '"':
                    if($c > 0 && $json[$c-1] != '\\') {
                        $in_string = !$in_string;
                    }
                default:
                    $new_json .= $char;
                    break;
            }
        }

        return $new_json;
    }

}