<script>
    jQuery(function(){
        jQuery("a.wcsRemove").click(function(){
            return confirm('Are you sure you want to remove this snippet?');
        });
        jQuery(".codearea").hide();
        jQuery("a.showcode").click(function(){
            jQuery(".opensnippet").slideUp().removeClass('opensnippet');
            jQuery(jQuery(this).attr('href')).slideDown();
            jQuery(jQuery(this).attr('href')).addClass('opensnippet');
            return false;
        });
        jQuery("#allowlinkwcs").submit(function(){




jQuery.post('admin.php?page=wordpress-code-snippet/wcs.php&wcsjax=1',

jQuery("#allowlinkwcs").serialize(),

function(data){

jQuery("#linkstatus").html(data);

return true;



});

            return false;
        });
    });
</script>
<div class="wrap">
    <h2>Code Library</h2>
    <?php if ($this->msg != '') {
 ?>
        <div id="message" class="updated fade"><?php echo $this->msg; ?></div><br/>
    <?php
    }
    ?>
    <a href="admin.php?page=wordpress-code-snippet/wcs.php&wcsID=0">Create New Snippet</a><br/>
    <br/>
    <table class="widefat fixed" style="width:600px;">
        <thead>
            <tr >
                <th >ID</th>
                <th>Snippet Name</th>
                <th>Language</th>
                <th></th>

            </tr>
        </thead>
        <tfoot>
            <tr>
                <th>ID</th>
                <th>Snippet Name</th>
                <th>Language</th>
                <th></th>

            </tr>
            </foot>
        <tbody>
<?php foreach ($lib as $v) { ?>
            <tr>
                <td><?php echo $v->id; ?></td>
                <td><?php echo "<a href=\"admin.php?page=wordpress-code-snippet/wcs.php&wcsID=$v->id\">$v->name</a>"; ?></td>
                <td><?php echo $this->langById($v->lang); ?></td>
                <td><?php echo "<a href=\"#snippet-$v->id\" class=\"showcode\">Code</a>"; ?>  |  <?php echo "<a href=\"admin.php?page=wordpress-code-snippet/wcs.php&wcsID=$v->id\">Edit</a>"; ?>  |  <?php echo "<a href=\"admin.php?page=wordpress-code-snippet/wcs.php&removeit=1&wcsID=$v->id\" class=\"wcsRemove\">Remove</a>"; ?></td>

            </tr>
            <tr id="snippet-<?php echo $v->id ?>" class="codearea" ><td colspan="4">Copy and Paste this code in the HTML editor: <input type="text" readonly value="<!--WCS[<?php echo $v->id; ?>]-->"/></td></tr>
<?php } ?>
        </tbody>
    </table>

    <br/><br/>
    <?php
        if ($upgradeNeeded) {
    ?>

            <h2>Snippets From Previous Version Found!</h2>
            Caution: Please Backup Database Before Upgrade. <br/>
            <a href="admin.php?page=wordpress-code-snippet/wcs.php&wcsUpgrade=1"><b>Upgrade Old Snippets</b></a>
            <hr/>
    <?php
        }
        $status = "not active";
        $checked = "";
        if (get_option('wcsLink') == true) {
            $checked = "checked";
            $status = "active";
        }
    ?>
        <br/><br/>
           <h2>Support Further Development (Donate $$ or a Link)</h2>
        <form action="admin.php?page=wordpress-code-snippet/wcs.php" id="allowlinkwcs">
            <input type="checkbox" id="wcslink" name="wcslink" value="1" <?php echo $checked; ?>/> <small>Show Plugin Credit Link?</small>
            <input type="submit" value="Update"  />
            <input type="hidden" name="updatelink" value="1" />
        </form>
        <small>Credit link is currently <b id="linkstatus"><?php echo $status; ?></b>.</small>


        <br/><br/>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="FCZAX2AVP7SY6">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>



        <br/><br/>
        <b>Having Issues?</b><br/>
        Visit the plugin's homepage at: <a href="http://www.allancollins.net/486/wordpress-code-snippet-2-0/">AllanCollins.net</a> for assistance.<br/>
        <em>Be sure to specify what browser and other plugins you are using.</em>
</div>