<?php

class Ld_View_Helper_PreferencesRenderer extends Zend_View_Helper_Abstract
{

	public $preferences = array();

	public $configuration = array();

	public $ns = null;

	public function setOptions($options = array())
	{
		$keys = array('preferences', 'configuration', 'ns');
		foreach ($keys as $key) {
			if (isset($options[$key])) $this->$key = $options[$key];
		}
		return $this;
	}

	public function preferencesRenderer($preferences = null, $configuration = null, $ns = null)
	{
		if (empty($preferences)) {
			return $this;
		}
		$options = compact('preferences', 'configuration', 'ns');
		$this->setOptions($options);
		return $this->renderTable();
	}

	public function renderTable()
	{
		echo '<table class="ld-preferences">';
		foreach ($this->preferences as $preference) {
			if (is_object($preference)) {
				$preference = $preference->toArray();
			}
			$name = $preference['name'];
			$id = isset($this->ns) ? $this->ns . $name : $name;
			if ($preference['type'] != 'hidden') {
				echo '<tr><th><label for="' . $id . '">' . $preference['label'] . '</label></th><td>' . "\n";
			}
			$this->renderControl($preference);
			if ($preference['type'] != 'hidden') {
				echo '</td></tr>' . "\n";
			}
		}
		echo '</table>';
	}

	public function renderInline()
	{
		foreach ($this->preferences as $preference) {
			if (is_object($preference)) {
				$preference = $preference->toArray();
			}
			if (empty($preference['type'])) {
				$preference['type'] = 'text';
			}
			$name = $preference['name'];
			$id = isset($this->ns) ? $this->ns . $name : $name;
			if ($preference['type'] != 'hidden') {
				echo '<p><label for="' . $id . '">' . $preference['label'] . '</label>' . "\n";
			}
			if (isset($preference['legend'])) {
				echo '<em>' . $this->view->escape($preference['legend']) . '</em><br/>' . "\n";
			}
			$this->renderControl($preference);
			if ($preference['type'] != 'hidden') {
				echo '</p>' . "\n";
			}
		}
	}

	public function renderControl($preference)
	{
		$name = $preference['name'];
		$id = isset($this->ns) ? $this->ns . $name : $name;
		$inputName = isset($this->ns) ? $this->ns . "[$name]" : $name;
		$value = isset($this->configuration[$name]) ? $this->configuration[$name] : null;
		if (!isset($value) && isset($preference['defaultValue'])) {
			$value = $preference['defaultValue'];
		}
		switch ($preference['type']) {
			case 'boolean':
				$checked = ($value == 'true' || $value == 1) ? ' checked="checked"' : '';
				echo '<input type="hidden" value="0" name="' . $inputName . '"/>' . "\n"; // not a best practice, but it does the job
				echo '<input type="checkbox" value="1" id="' . $id . '"  name="' . $inputName . '"' . $checked . ' />' . "\n";
				break;
			case 'password':
				echo '<input class="text" type="password" id="' . $id . '"  name="' . $inputName . '" value="' . $this->view->escape($value) . '" />' . "\n";
				break;
			case 'range':
				echo '<select id="' . $id . '" name="' . $inputName . '">' . "\n";
				for ($i = (int)$preference['min']; $i <= (int)$preference['max']; $i += $preference['step']) {
					$selected = (int)$value == $i ? ' selected="selected"' : '';
					echo '  <option value="' . $i . '"' . $selected . '>' . $i . '</option>' . "\n";
				}
				echo '</select>' . "\n";
				break;
			case 'list':
				echo '<select id="' . $id . '" name="' . $inputName . '">' . "\n";
				foreach ($preference['options'] as $option) {
					$selected = (string)$value == (string)$option['value'] ? ' selected="selected"' : '';
					echo '  <option value="' . $option['value']  . '"' . $selected . '>' . $option['label'] . '</option>' . "\n";
				}
				echo '</select>' . "\n";
				break;
			case 'hidden':
				echo '<input type="hidden" name="' . $inputName . '" value="' . $this->view->escape($value) . '" />' . "\n";
				break;
			case 'textarea':
				echo '<textarea cols="46" rows="5" id="' . $id . '" name="' . $inputName . '">' . $this->view->escape($value) . '</textarea>' . "\n";
				break;
			case 'text':
			default:
			    echo '<input class="text" size="45" type="text" id="' . $id . '" name="' . $inputName . '" value="' . $this->view->escape($value) . '" />' . "\n";
			break;
		}		
	}

}
