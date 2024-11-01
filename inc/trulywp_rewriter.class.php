<?php

/**
 * TrulyWP_Rewriter
 *
 * @since 0.0.1
 */

class TrulyWP_Rewriter
{
    var $blog_url       = null;    // origin URL
    var $cdn_url        = null;    // CDN URL

    var $dirs           = null;    // included directories
    var $excludes       = []; // excludes
    var $relative       = false;   // use CDN on relative paths
    var $https          = false;   // use CDN on HTTPS
    var $enabled        = false;

    /**
     * constructor
     *
     * @since   0.0.1
     * @change  1.0.5
     */

    function __construct(
        $blog_url,
        $cdn_url,
        $dirs,
        array $excludes,
        $relative,
        $https,
        $enabled
    ) {
        $this->blog_url       = $blog_url;
        $this->cdn_url        = $cdn_url;
        $this->dirs           = $dirs;
        $this->excludes       = $excludes;
        $this->relative       = $relative;
        $this->https          = $https;
        $this->enabled        = $enabled;
    }


    /**
     * exclude assets that should not be rewritten
     *
     * @since   0.0.1
     * @change  1.0.3
     *
     * @param   string  $asset  current asset
     * @return  boolean  true if need to be excluded
     */

    protected function exclude_asset(&$asset) {
        // excludes
        
        foreach ($this->excludes as $exclude) {            
            if (!!$exclude && stristr($asset, $exclude) != false) {
                return true;
            }
        }
        return false;
    }


    /**
     * relative url
     *
     * @since   1.0.5
     * @change  1.0.5
     *
     * @param   string  $url a full url
     * @return  string  protocol relative url
     */
    protected function relative_url($url) {
        return substr($url, strpos($url, '//'));
    }


    /**
     * rewrite url
     *
     * @since   0.0.1
     * @change  1.0.7
     *
     * @param   string  $asset  current asset
     * @return  string  updated url if not excluded
     */

    protected function rewrite_url(&$asset) {

        

        if ($this->exclude_asset($asset[0])) {
            return $asset[0];
        }

        // Don't rewrite if in preview mode
        if ( is_admin_bar_showing()
                and array_key_exists('preview', $_GET)
                and $_GET['preview'] == 'true' )
        {
            return $asset[0];
        }

        
        $blog_url = $this->relative_url($this->blog_url);
        $subst_urls = [ 'http:'.$blog_url ];

        // rewrite both http and https URLs if we ticked 'enable CDN for HTTPS connections'
        if ($this->https) {
            $subst_urls = [
                'http:'.$blog_url,
                'https:'.$blog_url,
            ];
        }


        // is it a protocol independent URL?
        if (strpos($asset[0], '//') === 0) {
            $new = str_replace($blog_url, $this->cdn_url, $asset[0]);
            error_log($new);
            return $new;
        }

        // check if not a relative path
        if (!$this->relative || strstr($asset[0], $blog_url)) {
            $new = str_replace($subst_urls, $this->cdn_url, $asset[0]);
            error_log($new);
            return $new;
        }

        $new = $this->cdn_url . $asset[0];

        error_log($new);

        // relative URL
        return $new;

        
    }


    /**
     * get directory scope
     *
     * @since   0.0.1
     * @change  0.0.1
     *
     * @return  string  directory scope
     */

    protected function get_dir_scope() {
        $input = explode(',', $this->dirs);

        // default
        if ($this->dirs == '' || count($input) < 1) {
            return 'wp\-content|wp\-includes';
        }

        return implode('|', array_map('quotemeta', array_map('trim', $input)));
    }


    /**
     * rewrite url
     *
     * @since   0.0.1
     * @change  1.0.7
     *
     * @param   string  $html  current raw HTML doc
     * @return  string  updated HTML doc with CDN links
     */

    public function rewrite($html) {
        // check if HTTPS and use CDN over HTTPS enabled
        if (!$this->https && isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') {
            return $html;
        }

        // get dir scope in regex format
        $dirs = $this->get_dir_scope();
        $blog_url = $this->https
            ? '(https?:|)'.$this->relative_url(quotemeta($this->blog_url))
            : '(http:|)'.$this->relative_url(quotemeta($this->blog_url));

        // regex rule start
        $regex_rule = '#(?<=[(\"\'])';

        // check if relative paths
        if ($this->relative) {
            $regex_rule .= '(?:'.$blog_url.')?';
        } else {
            $regex_rule .= $blog_url;
        }

        // regex rule end
        $regex_rule .= '/(?:((?:'.$dirs.')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';

        

        // call the cdn rewriter callback
        $cdn_html = preg_replace_callback($regex_rule, [$this, 'rewrite_url'], $html);

        error_log($blog_url.">> *HTML*");

        return $cdn_html;
    }
}
