<?php

class View_Helper_PreferencesRenderer extends Zend_View_Helper_Abstract
{
	public function setOptions($options = array())
	{
		$keys = array();
		foreach ($keys as $key) {
			if (isset($options[$key])) $this->$key = $options[$key];
		}
		return $this;
	}

	public function preferencesRenderer($preferences = array(), $configuration = array(), $ns = null)
	{
		if (empty($preferences)) {
			return $this;
		}
		return $this->render($preferences, $configuration, $ns);
	}

	function render($preferences = array(), $configuration = array(), $ns = null)
	{
		echo '<table>';
		foreach ($preferences as $preference) {
			$name = $preference['name'];
			$inputName = isset($ns) ? "$ns" . "[$name]" : $name;
			$value = isset($configuration[$name]) ? $configuration[$name] : null;
			if (!isset($value) && isset($preference['defaultValue'])) {
				$value = $preference['defaultValue'];
			}
			if ($preference['type'] != 'hidden') {
				echo '<tr><td><label>' . $preference['label'] . '</label></td><td>' . "\n";
			}
			switch ($preference['type']) {
				case 'boolean':
					$checked = ($value == 'true' || $value == 1) ? ' checked="checked"' : '';
					echo '<input type="checkbox" value="1" name="' . $inputName . '"' . $checked . ' />' . "\n";
					break;
				case 'password':
					echo '<input class="text" type="password" name="' . $inputName . '" value="' . $this->view->escape($value) . '" />' . "\n";
					break;
				case 'range':
					echo '<select name="' . $inputName . '">' . "\n";
					for ($i = (int)$preference['min']; $i <= (int)$preference['max']; $i += $preference['step']) {
						$selected = (int)$value == $i ? ' selected="selected"' : '';
						echo '  <option value="' . $i . '"' . $selected . '>' . $i . '</option>' . "\n";
					}
					echo '</select>' . "\n";
					break;
				case 'list':
					echo '<select name="' . $inputName . '">' . "\n";
					foreach ($preference['options'] as $option) {
						$selected = (string)$value == (string)$option['value'] ? ' selected="selected"' : '';
						echo '  <option value="' . $option['value']  . '"' . $selected . '>' . $option['label'] . '</option>' . "\n";
					}
					echo '</select>' . "\n";
					break;
				case 'hidden':
					echo '<input type="hidden" name="' . $inputName . '" value="' . $this->view->escape($value) . '" />' . "\n";
					break;
				case 'text':
				default:
				    echo '<input class="text" size="45" type="text" name="' . $inputName . '" value="' . $this->view->escape($value) . '" />' . "\n";
				break;
			}
			echo '</tr>';
		}
		echo '</table>';
	}
}