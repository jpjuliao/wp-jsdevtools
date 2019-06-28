<?php

/**
 * Plugin Name: WP-DevOps
 * Description: Run development operations via Javascript.  
 * Author: Juan Pablo Juliao
 * Author URI: jpjuliao.com
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Jpjuliao_WP_DevOps {

    public $root;

    public function __construct() {
		$this->root = plugin_dir_path(__FILE__).'../../';
        add_action('wp_ajax_devops', [$this, 'init']);
        add_action('wp_head', [$this, 'js']);
        add_action('admin_head', [$this, 'js']);
    }
        
    public function init() {
        if (!current_user_can('manage_options')) {
            echo 'User not allowed.';
            wp_die();
        }

        if (empty($_POST)) {
            echo 'Please enter parameters. More info: https://github.com/jpjuliao/wp-devops';
            wp_die();
		}
		
        if (empty($_POST['action'])) {
            echo 'Please enter an action.';
            wp_die();
		}

        if ($_POST['action'] == 'help') {
            echo 'More info: https://github.com/jpjuliao/wp-devops';
            wp_die();
		}
        
        switch ($_POST['git']) {
            case 'config'   : $this->git_config(); break;
            case 'pull'     : $this->git_pull(); break;
            case 'status'   : $this->git_status(); break;
            default         : echo 'Please enter a valid git command.';
        }
		
        wp_die();
    }

    public function js() { ?>
        <script type="text/javascript">
            function devops($params) {
                'use strict';
                if (typeof $params === 'undefined') {
                    const $params = {action:'help'};
                }
                else if (typeof $params === 'object' && $params !== null) {
                    $params.action = 'devops';
                }
                else {
                    return;
                }
                jQuery.post(
                    '<?php echo admin_url( "admin-ajax.php" ); ?>', 
                    $params, 
                    function(response) {
                        console.log('# WP-DevOps Response', '\n\n', response);
                    }
                );
            }
        </script><?php
    }

    private function git_config() {

        if (empty($_POST['repo'])) {
            echo 'Please enter repo parameter.';
            wp_die();
        }

        $dir = $this->root.$_POST['repo'];
        $config_file = $dir.'/.git/config';
        if (!file_exists($config_file)) {
            echo 'Git config file not found.';
            wp_die();
        }
        $file = file($config_file);
            
        foreach($file as $line) echo $line;
        wp_die();
    
    } 
        
    private function git_pull() {

        if (empty($_POST['repo'])) {
            echo 'Please enter repo parameter.';
            wp_die();
        }

        $dir = $this->root.$_POST['repo'];
        $url = exec('cd '.$dir.'; git config --get remote.origin.url');
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo 'Remote origin URL not found.';
            wp_die();
		}
		
        if (!empty($_POST['login'])) {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (strpos($url, '@') !== false) {
                $url = $scheme.
                    '://'.$_POST['login'].
                    explode('@', $url)[1];
            } else {
                $url = str_replace(
                    $scheme.'://',
                    $scheme.'://'.$_POST['login'].'@',
                    $url
                );
            }
        }
        
        $command = 'cd '.$dir.'; git pull '.$url.
            (isset($_POST['branch']) ? ' '.$_POST['branch'] : '');
            
        $output = [];
        exec($command, $output);
        foreach($output as $line) echo $line.PHP_EOL;
        wp_die();
    }

    private function git_status() {
        if (empty($_POST['repo'])) {
            echo 'Please enter repo parameter.';
            wp_die();
        }
        
        $command = 'cd '.$this->root.$_POST['repo'].
            '; git status';
        
        $output = [];
        exec($command, $output);
        foreach($output as $line) echo $line.PHP_EOL;
        wp_die();
    }

    private function git_clone() {
        // Todo
    }

}

new Jpjuliao_WP_DevOps();
