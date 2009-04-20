<?php

if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'action.php');

class action_plugin_ld extends DokuWiki_Action_Plugin {

	/**
	 * Return some info
	 */
	function getInfo()
	{
		return array(
			'author' => 'h6e.net',
			'email'  => 'contact@h6e.net',
			'date'   => '2008-02-17',
			'name'   => 'La Distribution package',
			'desc'   => 'support for La Distribution prepend/append file based extension mechanism',
			'url'    => 'http://h6e.net/dokuwiki/plugins/ld',
		);
	}

	/**
	 * Register the eventhandlers
	 */
	function register(&$controller)
	{
		$controller->register_hook('DOKUWIKI_STARTED',
			'BEFORE',
			$this,
			'prepend',
			array());
		$controller->register_hook('DOKUWIKI_DONE',
			'BEFORE',
			$this,
			'append',
			array());
		$controller->register_hook('TPL_METAHEADER_OUTPUT',
			'BEFORE',
			$this,
			'tpl_metaheader_output',
			array());
		$controller->register_hook('TPL_CONTENT_DISPLAY',
			'AFTER',
			$this,
			'template',
			array());
	}

	function prepend(&$event, $param)
	{
		if (file_exists(DOKU_INC . 'dist/prepend.php')) {
			include DOKU_INC . 'dist/prepend.php';
		}
	}

	function append(&$event, $param)
	{
		if (file_exists(DOKU_INC . 'dist/append.php')) {
			include DOKU_INC . 'dist/append.php';
		}
	}

	function tpl_metaheader_output(&$event, $param)
	{
		$scripts = array(
			array('type' => 'text/javascript', '_data' => '', 'src' => LD_JS_URL . 'jquery/jquery.js'),
			array('type' => 'text/javascript', '_data' => '', 'src' => LD_JS_URL . 'ld/ld.js')
		);
		$event->data['script'] = array_merge($scripts, $event->data['script']);

		$event->data['link'][] = array( 'rel'=>'stylesheet', 'type'=>'text/css', 'href'=>LD_CSS_URL.'ld-ui/ld-bars.css');
		$event->data['link'][] = array( 'rel'=>'stylesheet', 'type'=>'text/css', 'href'=>LD_CSS_URL.'ld-ui/ld-dialog.css');
	}

	function template(&$event, $param)
	{
		require_once('Ld/Ui.php');
		Ld_Ui::super_bar();
		?>
		<script type="text/javascript">
		(function($) {
			$(document).ready(function($){
				$(".h6e-top-bar .action.login").click(function() {
					return Ld.handleLogin({
						'href': $(this).attr('href'),
						'baseUrl': '<?php echo LD_ADMIN_URL ?>',
						'wrapper': ".dokuwiki",
						'data': { 'id': '<?php echo $ID ?>', 'do': 'openid', 'mode': 'login' }
					});
				});
			});
		})(jQuery);
		</script>
		<?php
	}

}

function ld_top_bar()
{
	global $conf;
	?>
	<div class="h6e-top-bar">
		<div class="h6e-top-bar-inner">
			<div class="a">
				<?php echo $conf['title'] ?>
			</div>
			<div class="b">
				<?php tpl_userinfo()?>
				<?php tpl_actionlink('subscription') ?>
				<?php tpl_actionlink('profile') ?>
				<?php tpl_actionlink('admin') ?>
				<?php tpl_actionlink('login'); ?>
			</div>
		</div>
	</div>
	<?php
}
