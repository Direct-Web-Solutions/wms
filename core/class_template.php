<?php
/**
 * 
 * core/class_template.php
 * WMS (Website Management System)
 *
 * @category    core files
 * @package     wms
 * @author      Darryn Fehr
 * @copyright   2018 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/license
 * @version     2.0.0
 * @release     June 5, 2021
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 2.0.0
 * @deprecated  File deprecated in Release 3.0.0
 * 
**/

class templates {
    
    public $template_count = 0;
    public $cache = array();
    
    function add_cached_template($title, $mode = "SQL", $path = NULL, $escape_slashes = FALSE) {
        global $db, $config, $lang;
        $mode = sys_strtolower($mode);
        if (isset($this->cache[$title]) && $mode === $this->cache[$title]['mode']) {
            return TRUE;
        } else {
            $content = NULL;
            switch ($mode) {
                case "sql":
                    $result = $db->prepared_select("templates", "WHERE title = ? AND is_active = ?", array($title, 1));
                    if ($result) {
                        $content = $result;
                    }
                    break;
                case "html":
                    if (isset($path)) {
                        $path = rtrim($path, '/');
                        $path = ltrim($path, '/');
                    }
                    if ($result = file_get_contents(dirname(dirname(__FILE__)) . "/templates/" . $path . "/". $title . ".html", true)) {
                        $path = dirname(dirname(__FILE__)) . "/templates/" . $path . "/". $title . ".html";
                        $content['template'] = $result;
                    }
                    break;
            }
            if ($content) {
                if ($escape_slashes) {
                    $content = str_replace("\\'", "'", addslashes($content));
                }
                if ($config->general['use_cdn']) {
                    $use_url_scheme = "cdn_url";
                    $add_asset_dir = "";
                } else {
                    $use_url_scheme = "base_url";
                    $add_asset_dir = "assets/";
                }
                $replace =  array (
                    '%%YEAR%%' => date("Y"),
                    '%%AUTHOR%%' => $config->general['meta_author'],
                    '%%SUPPORT%%' => $config->general['support_email'],
                    '%%METANAME%%' => $config->general['meta_name'],
                    '%%SITE_TITLE%%' => $config->general['site_title'],
                    '%%LANGAGE_HTML%%' => $lang->settings["htmllang"],
                    '%%LANGUAGE_CHARSET%%' => $lang->settings["charset"],
                    '%%APPTITLE%%' => $config->general['app_title'],
                    '%%ASSET_DOMAIN%%' => "//" . $config->general[$use_url_scheme] . $add_asset_dir,
                    '%%CANONICAL%%' => 'https://' . $config->general['base_url'] . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
                    '%%DOMAIN%%' => '//' . $config->general['base_url'],
                    '%%PANEL%%' => '//' . $config->general['base_url'] . "/" . $config->admin['ap_directory'],
                    '%%CDN%%' => '//' . $config->general['cdn_url'],
                    '%%TWITTER%%' => $config->general['socials']['twitter'],
                    '%%FACEBOOK%%' => $config->general['socials']['facebook'],
                    '%%LINKEDIN%%' => $config->general['socials']['linkedin'],
                    '%%VERSIONCODE%%' => 'rev=' $config->general['revision_code']
                );
                $content = str_replace(array_keys($replace), array_values($replace), $content);
                $this->cache[$title] = array(
                    'title'     =>  $title,
                    'mode'      =>  $mode,
                    'path'      =>  $path,
                    'is_esc'    =>  $escape_slashes,
                    'content'   =>  $content
                );
                $this->template_count++;
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }
    
    function sort_menu_array(array &$elements, $parentId = 0) {
        $_sorted = array();
        foreach ($elements as &$element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->sort_menu_array($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $_sorted[$element['id']] = $element;
                unset($element);
            }
        }
        return $_sorted;
    }
    
    function construct_rendered_menu(array $data, $replace, $slug = null, $load_additional_ul = true) {
        $html = "";
        if ($load_additional_ul) {
            $html .= "\n<ul>\n";
        }
        foreach ($data as $_menu_item) {
            $html .= "<li>";
            $title = "";
            $active_slug = "";
            if (isset($_menu_item['id']) && $_menu_item['id'] == $slug && $_menu_item['parent_id'] == 0) {
                $active_slug = " class=\"active\"";
            }
            if (isset($_menu_item['hover_title'])) {
                $title_text = htmlspecialchars($_menu_item['hover_title'], ENT_QUOTES);
                $title .= " title=\"{$title_text}\"";
            }
            if (isset($_menu_item['is_link']) && isset($_menu_item['link']) && $_menu_item['is_link'] > 0) {
                $link = htmlspecialchars($_menu_item['link'], ENT_QUOTES);
                $link = str_replace(array_keys($replace), array_values($replace), $link);
                $html .= "<a{$active_slug}{$title} href=\"{$link}\">" . $_menu_item['content'];
            }
            if (isset($_menu_item['children'])) {
                $html .= " <span style=\"font-size:11px;\"><i class=\"glyphicon glyphicon-chevron-down\"></i></span></a>";
                $html .= $this->construct_rendered_menu($_menu_item['children'], $replace);
            } else {
                $html .= "</a>";
            }
            $html .= "</li>\n";
        }
        if ($load_additional_ul) {
            $html .= "</ul>\n";
        }
        return $html;
    }

    function bundle_acp_menu($slug, $menu_array, $replace) {
        $_menu = $this->construct_rendered_menu($menu_array, $replace, $slug, false);
        return $_menu;
    }
    
    function build_acp_nav_from_db($slug = 1) {
        global $db, $config;
        $replace = array(
            '%%PANEL%%' => '//' . $config->general['base_url'] . "/" . $config->admin['ap_directory']
        );
        $menu_items = $db->prepared_select("admin_menu_items", "WHERE is_active=?", array(1));
        if ($menu_items) {
            $menu_array = $this->sort_menu_array($menu_items);
        } else {
            return "Unable to load menu items from the database.";
        }
        $admin_menu = $this->bundle_acp_menu($slug, $menu_array, $replace);
        return $admin_menu;
    }
    
    function generate_acp_menu($slug = 1) {
        $this->add_cached_template("menu", "HTML", "static/admin");
        $this->inject_variables("menu", $this->build_acp_nav_from_db($slug));
        return $this->cache['menu']['content']['template'];
    }
    
    function load_page_information($page_id = NULL) {
        if (isset($page_id)) {
            global $db;
            $page_information_query = $db->prepared_select("page_information", "WHERE page_name=? AND is_active=? LIMIT 1", array($page_id, 1));
            if ($page_information_query) {
                return array(0 => $page_information_query['page_title'], 1 => $page_information_query['page_description']);
            } else {
                return array(0 => "Error", 1 => "The page information was unable to be loaded from the database.");
            }
        } else {
            return array(0 => "Error", 1 => "The page information was unable to be loaded from the database.");
        }
    }
    
    function generate_error($error_code) {
        global $wms, $lang;
        http_response_code($error_code);
        $this->add_cached_template("error_template");
        switch ($error_code) {
            case 403:
                $error_type = "Access Denied";
                $error_message = "You do not have access to view this section. Please return to the homepage.";
                break;
            case 404:
                $error_type = "File not found";
                $error_message = "This page or resource does not exist. Please return to the homepage.";
                break;
            case 500:
                $error_type = "Internal Error";
                $error_message = "There is an internal server error at this time. Please try again later.";
                break;
            default:
                $error_type = "Unknown Error";
                $error_code = "Err";
                $error_message = "There has been an unexpected error. Please return to the homepage.";
                break;
        }
        $this->inject_variables("error_template", array($error_type, $error_message, $error_code));
        $this->render("error_template", true);
        $wms->close();
    }
    
    function remove_cached_template($title, $mode = "SQL") {
        $mode = sys_strtolower($mode);
        if (isset($this->cache[$title]) && $mode === $this->cache[$title]['mode']) {
            unset($this->$cache[$title]);
            return TRUE;
            $template_count--;
        }
        return FALSE;
    }
    
    function create_headers($inAdmin = false, $overRideNoCache = false) {
	    global $config, $lang, $wms;
	    header('X-Frame-Options: SAMEORIGIN');
	    if ($inAdmin) {
            header('Referrer-Policy: no-referrer');
	    } else {
            header('Referrer-Policy: strict-origin-when-cross-origin');
	    }
    	if ($config->general['use_nocache_headers'] || $overRideNoCache) {
    		header("Cache-Control: no-cache, private");
    	}
        if (function_exists('mb_internal_encoding') && !empty($lang->settings['charset'])) {
    	    @mb_internal_encoding($lang->settings['charset']);
        }
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        header("X-Content-Type-Options: nosniff");
        header("Permissions-Policy: fullscreen=('self'), geolocation=('self')");
        header("Content-type: text/html; charset={$lang->settings['charset']}");
    }

    function list_templates($filter_by_type = FALSE, $type = "SQL") {
        $templates = array();
        if (is_array($this->cache) && !empty($this->cache)) {
            if ($filter_by_type && isset($type)) {
                $type = sys_strtolower($type);
                switch ($type) {
                    case "html":
                        foreach ($this->cache as $template) {
                            if ("html" === $template["mode"]) {
                                $templates[] = $template['title'];
                            }
                        }
                        break;
                    case "sql":
                        foreach ($this->cache as $template) {
                            if ("sql" === $template["mode"]) {
                                $templates[] = $template['title'];
                            }
                        }
                        break;
                    default:
                        $templates = $this->cache;
                        break;
                }
            } else {
                $templates = $this->cache;
            }
        }
        if (!empty($templates)) {
            return $templates;
        }
        return "There are no templates currently loaded.";
    }
    
    function create_page_object($page_id) {
        global $db;
        $set_data = $db->prepared_select("template_sets", "WHERE set_name = ? AND set_active = ?", array($page_id, 1));
        if ($set_data) {
            $dataObj = json_decode($set_data["set_object"], true);
            if (is_array($dataObj)) {
                foreach ($dataObj as $template) {
                    $this->add_cached_template($template["template_name"], $template["template_type"], $template["template_path"], $template["escape_slashes"]);
                }
                return TRUE;
            }
        }
		return FALSE;
    }

    function inject_variables($template, $variable_set) {
        if ($this->cache[$template]) {
            if (is_array($variable_set)) {
                $count = 1;
                foreach ($variable_set as $variable_replace) {
                    $this->cache[$template]["content"] = str_replace('%%VAR' . $count . '%%', $variable_replace, $this->cache[$template]["content"]);
                    $count++;
                }
            } else {
                $this->cache[$template]["content"] = str_replace('%%VAR1%%', $variable_set, $this->cache[$template]["content"]);
            }
        }
    }
    
    function render($template, $end_of_file = FALSE) {
        if ($this->cache[$template]) {
            $template = $this->cache[$template]["content"]["template"];
            if (!$end_of_file) {
                $template = $template . "\n";
            }
            echo $template;
        } else {
            echo "Unable to load this template from the cache.";
        }
    }
    
}
