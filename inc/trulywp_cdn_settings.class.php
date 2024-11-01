<?php

/**
 * TrulyWP_CDN_Settings
 *
 * @since 0.0.1
 */

class TrulyWP_CDN_Settings
{


    /**
     * register settings
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function register_settings()
    {
        register_setting(
            'trulywp-cdn',
            'trulywp-cdn',
            [
                __CLASS__,
                'validate_settings',
            ]
        );
    }


    /**
     * validation of settings
     *
     * @since   0.0.1
     * @change  1.0.5
     *
     * @param   array  $data  array with form data
     * @return  array         array with validated values
     */

    public static function validate_settings($data)
    {

        

        if (!isset($data['relative'])) {
            $data['relative'] = 0;
        }
        if (!isset($data['https'])) {
            $data['https'] = 0;
        }
        if (!isset($data['enabled'])) {
            $data['enabled'] = 0;
        }


        return [
            'dirs'            => esc_attr($data['dirs']),
            'excludes'        => esc_attr($data['excludes']),
            'relative'        => (int)($data['relative']),
            'https'           => (int)($data['https']),
            'enabled'         => (int)($data['enabled']),
        ];
    }


    /**
     * add settings page
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function add_settings_page()
    {
        $page = add_options_page(
            'TrulyWP CDN',
            'TrulyWP CDN',
            'manage_options',
            'trulywp-cdn',
            [
                __CLASS__,
                'settings_page',
            ]
        );
    }


    /**
     * settings page
     *
     * @since   0.0.1
     * @change  1.0.6
     *
     * @return  void
     */

    public static function settings_page()
    {
        $options = TrulyWP_CDN::get_options()


      ?>
        <div class="wrap">
           <h3>Content Delivery Network (CDN) settings</h3>

           <form method="post" action="options.php">
               <?php settings_fields('trulywp-cdn') ?>

               <table class="form-table">

                    <tr valign="top">
                       <th scope="row">
                           <?php _e("Enable CDN", "trulywp"); ?>
                       </th>
                       <td>
                           <fieldset>
                               <label for="trulywp_enabled">
                                   <input type="checkbox" name="trulywp-cdn[enabled]" id="trulywp_enabled" value="1" <?php checked(1, $options['enabled']) ?> />
                                   <?php _e("Enable CDN for this blog", "trulywp"); ?>
                               </label>
                           </fieldset>
                       </td>
                   </tr>

                   <tr valign="top">
                       <th scope="row">
                           <?php _e("Included Directories", "trulywp"); ?>
                       </th>
                       <td>
                           <fieldset>
                               <label for="trulywp_dirs">
                                   <input type="text" name="trulywp-cdn[dirs]" id="trulywp_dirs" value="<?php echo $options['dirs']; ?>" size="64" class="regular-text code" />
                                   <?php _e("Default: <code>wp-content,wp-includes</code>", "trulywp"); ?>
                               </label>

                               <p class="description">
                                   <?php _e("Assets in these directories will be pointed to the CDN URL. Enter the directories separated by", "trulywp"); ?> <code>,</code>
                               </p>
                           </fieldset>
                       </td>
                   </tr>

                   <tr valign="top">
                       <th scope="row">
                           <?php _e("Exclusions", "trulywp"); ?>
                       </th>
                       <td>
                           <fieldset>
                               <label for="trulywp_excludes">
                                   <input type="text" name="trulywp-cdn[excludes]" id="trulywp_excludes" value="<?php echo $options['excludes']; ?>" size="64" class="regular-text code" />
                                   <?php _e("Default: <code>.php</code>", "trulywp"); ?>
                               </label>

                               <p class="description">
                                   <?php _e("Enter the exclusions (directories or extensions) separated by", "c"); ?> <code>,</code>
                               </p>
                           </fieldset>
                       </td>
                   </tr>

                   <tr valign="top">
                       <th scope="row">
                           <?php _e("Relative Path", "trulywp"); ?>
                       </th>
                       <td>
                           <fieldset>
                               <label for="trulywp_relative">
                                   <input type="checkbox" name="trulywp-cdn[relative]" id="trulywp_relative" value="1" <?php checked(1, $options['relative']) ?> />
                                   <?php _e("Enable CDN for relative paths (default: enabled).", "trulywp"); ?>
                               </label>
                           </fieldset>
                       </td>
                   </tr>

                   <tr valign="top">
                       <th scope="row">
                           <?php _e("CDN HTTPS", "trulywp"); ?>
                       </th>
                       <td>
                           <fieldset>
                               <label for="trulywp_https">
                                   <input type="checkbox" name="trulywp-cdn[https]" id="trulywp_https" value="1" <?php checked(1, $options['https']) ?> />
                                   <?php _e("Enable CDN for HTTPS connections (default: disabled).", "trulywp"); ?>
                               </label>
                           </fieldset>
                       </td>
                   </tr>

               </table>

               <?php submit_button() ?>
           </form>
        </div><?php
    }
}
