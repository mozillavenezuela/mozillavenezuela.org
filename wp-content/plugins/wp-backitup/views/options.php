<div class="wrap">
    <div id="wp-backitup-icon" class="icon32"><img src="<?php echo plugin_dir_url(dirname(__FILE__) ); ?>images/icon32.png" alt="WP Backitup Icon" height="32" width="32" /></div>
    <h2><?php echo $page_title; ?></h2>
    <div id="content">
        <h3><?php _e('Backup', $namespace );?></h3>
        <p><?php _e('Create a backup file of this site\'s content and settings.', $namespace ) ;?></p>
        <p><a href="#" class="backup-button button-primary"><?php _e( "Backup", $namespace ) ?></a><img class="backup-icon status-icon" src="<?php echo WPBACKITUP_URLPATH. "/images/loader.gif"; ?>" height="16" width="16" /></p>
        <h3><?php _e('Download', $namespace );?></h3>
        <p id="download-link"></p>
        
        <!--Disable restoration form if the user has not activated-->
        <?php $status = $this->get_option( 'status' );
                if( $status !== false && $status == 'valid' ) { ?>
        <h3><?php _e('Restore', $namespace );?></h3>
        <iframe id="upload_target" name="upload_target" src=""></iframe>
        <p><?php _e('Restore a WP Backitup zip file and overwrite this site\'s content, themes, plugins, uploads and settings.', $namespace );?></p>
        <?php $max_upload = (int)(ini_get('upload_max_filesize'));
            $max_post = (int)(ini_get('post_max_size'));
            $memory_limit = (int)(ini_get('memory_limit'));
            $upload_mb = min($max_upload, $max_post, $memory_limit); ?>
        <p><?php _e( 'The maximum filesize you can upload is ', $namespace ); 
            echo $upload_mb .'MB.'; ?>
        </p>
        <form id="restore-form" method="post" enctype="multipart/form-data" action="<?php echo WPBACKITUP_URLPATH .'/lib/includes/restore.php'; ?>"> 
           <?php global $current_user; ?>
            <input type="hidden" name="user_id" value="<?php echo $current_user->ID; ?>" />
            <input type="hidden" name="maximum" id="maximum" value="<?php echo $upload_mb; ?>" />
            <p><input name="wpbackitup-zip" id="wpbackitup-zip" type="file" /></p> 
            <p><input type="submit" class="restore-button button-primary" name="restore" value="<?php _e( "Restore", $namespace ) ?>" /><img class="restore-icon status-icon" src="<?php echo WPBACKITUP_URLPATH. "/images/loader.gif"; ?>" height="16" width="16" /></p>
        </form>
        <?php } ?>
        <!--End of restoration form-->
        
        <h3><?php _e('Status', $namespace );?></h3>
        <p><div id="status"><?php _e('Nothing to report', $namespace );?></div></p>
        <?php if (site_url() == 'http://localhost/wpbackitup') {
            echo '<p><div id="php">PHP messages here</div></p>'; 
        } ?>
    </div>
    <div id="sidebar">
        <form action="" method="post" id="<?php echo $namespace; ?>-form">
        <?php wp_nonce_field( $namespace . "-update-options" ); ?>
        <div class="widget">
            <h3 class="promo"><?php _e('License Key', $namespace ); ?></h3>
            <?php $license = $this->get_option( 'license_key' );
                $status = $this->get_option( 'status' );
                if( $status !== false && $status == 'valid' ) { ?>
                    <p><?php _e('Pro features and auto-updates enabled.', $namespace ); ?></p>
                <?php } else { ?>
                    <p><?php _e('Activate auto-restore and auto-updates by entering your license key.', $namespace ); ?></p>
                <?php } ?>
                <p><input type="text" name="data[license_key]" id="license_key" value="<?php esc_attr_e( $license ); ?>">
                <?php if( false !== $license ) { 
                    if( $status !== false && $status == 'valid' ) { ?>
                        <span style="color:green;"><?php _e('Active', $namespace); ?></span></p>
                        <p class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e( "Update", $namespace ) ?>" /></p>
                    <?php } else { ?>
                        <span style="color:red;"><?php _e('Inactive', $namespace); ?></span></p>
                        <p class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e( "Activate", $namespace ) ?>" /></p>
                        <p><a href="http://www.wpbackitup.com/wp-backitup-pro"><?php _e('Purchase a license key', $namespace); ?></a></p>
                    <?php } 
                } ?>               
        </div>
        <div class="widget">
            <?php if( $status !== false && $status == 'valid' ) {
                    $support_anchor = __('WP Backitup support system',$namespace);
                    $support_url = 'http://www.wpbackitup.com/support/';
                } else {
                    $support_anchor = __('support system',$namespace);
                    $support_url = 'http://wordpress.org/support/plugin/wp-backitup';
                } ?>
            <h3 class="promo"><?php _e('Need Help?', $namespace ); ?></h3>
            <p><?php _e('Access the',$namespace); ?> <a href="<?php echo $support_url; ?>"><?php echo $support_anchor; ?></a>.</p>
                
        </div>
        <div class="widget">
            <h3 class="promo"><?php _e('Spread the Word', $namespace ); ?></h3>
            <p><a href="http://wordpress.org/extend/plugins/wp-backitup/"><?php _e('Rate WP Backitup', $namespace ); ?> 5&#9733;</a></p>
        </div>
        <div class="widget">
            <h3 class="promo">Presstrends</h3>
                <p><input type="radio" name="data[presstrends]" value="enabled" <?php if($this->get_option( 'presstrends' ) == 'enabled') echo 'checked'; ?>> <label><?php _e('Enable', $namespace ); ?></label></p>
                <p><input type="radio" name="data[presstrends]" value="disabled" <?php if($this->get_option( 'presstrends' ) == 'disabled') echo 'checked'; ?>> <label><?php _e('Disable', $namespace ); ?></label></p>
                <p><?php _e('Help to improve Easy Webtrends by enabling', $namespace ); ?> <a href="http://www.presstrends.io" target="_blank">Presstrends</a>.</p>
                <p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php _e( "Save", $namespace ) ?>" /></p>
        </div>
        </form>
    </div>
</div>