<?php
/*
Plugin Name: Shortcut Macros
Plugin URI: http://alexking.org/projects/wordpress
Description: Auto-expansion macros - for example to turn '##wp.org' into '&lt;a href=&quot;http://wordpress.org&quot;&gt;WordPress&lt;a&gt;'. <a href="options-general.php?page=shortcut-macros.php">Create and modify them here</a>.
Version: 1.1
Author: Alex King
Author URI: http://alexking.org
*/ 

load_plugin_textdomain('alexking.org');

function aksm_process_content($content) {
	$macros = unserialize(get_option('aksm_macros'));
	if (is_array($macros) && count($macros) > 0) {
		$find = array();
		$replace = array();
		foreach ($macros as $k => $v) {
			$find[] = '##'.$k;
			$replace[] = mysql_real_escape_string($v);
		}
		$content = str_replace($find, $replace, $content);
	}
	return $content;
}
add_action('content_save_pre', 'aksm_process_content');

function aksm_options_form() {
	$macros = get_option('aksm_macros');
	if ($macros != '') {
		$macros = unserialize($macros);
	}
	else {
		$macros = array();
	}
	print('
		<div class="wrap">
			<h2>'.__('Shortcut Macros', 'alexking.org').'</h2>
			<form name="ak_shortcutmacros" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post" onsubmit="if (!aksm_enabled) { return false; }">
				<input type="hidden" name="ak_action" value="update_aksm_settings" />
				<fieldset class="options">
					<table style="width: 100%;">
						<thead>
							<th>'.__('Macro', 'alexking.org').'</th>
							<th>'.__('Replace With', 'alexking.org').'</th>
						</thead>
						<tbody id="aksm_macros">
	');
	$i = 1;
	if (count($macros) > 0) {
		foreach ($macros as $k => $v) {
			if ($i % 2 != 0) {
				$class = 'alternate';
			}
			else {
				$class = '';
			}
			print('
							<tr class="'.$class.'" id="aksm_'.$i.'">
								<td>##<input type="text" name="aksm_k_'.$i.'" value="'.htmlspecialchars($k).'" size="15"  /></td>
								<td><input type="text" name="aksm_v_'.$i.'" value="'.htmlspecialchars($v).'" size="70"  /></td>
								<td><input type="button" onclick="void(document.getElementById(\'aksm_'.$i.'\').parentNode.removeChild(document.getElementById(\'aksm_'.$i.'\')));" value="X" /></td>
							</tr>
			');
			$i++;
		}
	}
	if ($i % 2 != 0) {
		$class = 'alternate';
	}
	else {
		$class = '';
	}
	print('
							<tr class="'.$class.'" id="aksm_'.$i.'">
								<td>##<input type="text" name="aksm_k_'.$i.'" value="" size="15"  /></td>
								<td><input type="text" name="aksm_v_'.$i.'" value="" size="70"  /></td>
								<td><input type="button" id="aksm_add_'.$i.'" onclick="void(aksm_add_macro(\''.($i + 1).'\'));" value="+" /><input type="button" id="aksm_del_'.$i.'" style="display: none;" onclick="void(document.getElementById(\'aksm_'.$i.'\').parentNode.removeChild(document.getElementById(\'aksm_'.$i.'\')));" value="X" /></td>
							</tr>
	');
	print('
						</tbody>
					</table>
				</fieldset>
				<p class="submit">
					<input type="submit" name="submit_button" onclick="aksm_enabled = true;" value="'.__('Update Shortcut Macros', 'alexking.org').'" />
				</p>
			</form>
		</div>
	');
}
function aksm_admin_menu() {
	if (function_exists('add_options_page')) {
		add_options_page(
			__('Shortcut Macro Options', 'alexking.org')
			, __('Macros', 'alexking.org')
			, 10
			, basename(__FILE__)
			, 'aksm_options_form'
		);
	}
}
add_action('admin_menu', 'aksm_admin_menu');

function aksm_admin_head() {
	print('
		<script type="text/javascript" src="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?ak_action=aksm_js"></script>
	');
}
add_action('admin_head', 'aksm_admin_head');

function aksm_request_handler() {
	global $wpdb, $aklh;
	if (!empty($_POST['ak_action'])) {
		switch($_POST['ak_action']) {
			case 'update_aksm_settings':
				$macros = array();
				foreach ($_POST as $k => $v) {
					if (substr($k, 0, 7) == 'aksm_k_') {
						$num = str_replace('aksm_k_', '', $k);
						if (!empty($_POST['aksm_k_'.$num]) && !empty($_POST['aksm_v_'.$num])) {
							$key = str_replace(' ', '_', stripslashes($_POST['aksm_k_'.$num]));
							$macros[$key] = stripslashes($_POST['aksm_v_'.$num]);
						}
					}
				}
				ksort($macros);
				if (get_option('aksm_macros') == '') {
					add_option('aksm_macros', serialize($macros));
				}
				else {
					update_option('aksm_macros', serialize($macros));
				}
				header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page='.basename(__FILE__).'&updated=true');
				die();
				break;
		}
	}
	if (!empty($_GET['ak_action'])) {
		switch($_GET['ak_action']) {
			case 'aksm_js':
?>
var aksm_enabled = false;

function aksm_add_macro(num) {
	document.getElementById('aksm_add_' + (parseInt(num) - 1)).style.display = 'none';
	document.getElementById('aksm_del_' + (parseInt(num) - 1)).style.display = 'block';
	var tr = document.createElement('TR');
	tr.id = 'aksm_' + num;
	if (num % 2 != 0) {
		tr.className = 'alternate';
	}
		var td1 = document.createElement('TD');
			var in1 = document.createElement('INPUT');
			in1.name = 'aksm_k_' + num;
			in1.id = 'aksm_k_' + num;
			in1.size = 15;
		td1.innerHTML = '##';
		td1.appendChild(in1);
	tr.appendChild(td1);
		var td2 = document.createElement('TD');
			var in2 = document.createElement('INPUT');
			in2.name = 'aksm_v_' + num;
			in2.size = 70;
		td2.appendChild(in2);
	tr.appendChild(td2);
		var td3 = document.createElement('TD');
		td3.innerHTML = '<input type="button" id="aksm_add_' + num + '" onclick="void(aksm_add_macro(\'' + (parseInt(num) + 1) + '\'));" value="+" /><input type="button" id="aksm_del_' + num + '" style="display: none;" onclick="void(document.getElementById(\'aksm_' + num + '\').parentNode.removeChild(document.getElementById(\'aksm_' + num + '\')));" value="X" />';
	tr.appendChild(td3);
	document.getElementById('aksm_macros').appendChild(tr);
	document.getElementById('aksm_k_' + num).focus();
}
<?php
				die();
				break;
		}
	}
}
add_action('init', 'aksm_request_handler');

?>