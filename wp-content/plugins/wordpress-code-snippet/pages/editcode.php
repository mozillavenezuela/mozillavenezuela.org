
<script type="text/javascript">
    jQuery(function() {
        jQuery("#editArea").resizable({
            handles: "se",
            maxWidth: 600
        });

    });
</script>

<div class="wrap">

    <h2><?php echo $titleType; ?> Snippet</h2>
    <a href="admin.php?page=wordpress-code-snippet/wcs.php">&lArr; Back to Code Library</a><br/><br/>
    <?php if ($this->msg != '') { ?>
    <div id="message" class="updated fade"><?php echo $this->msg; ?></div>
        <?php
    }
    ?>
    <div id="wsc-edit" class="ui-widget-header">
        <form id="wcsForm" action="admin.php?page=wordpress-code-snippet/wcs.php&wcs-update=1&wcsID=<?php echo $snip->id; ?>" method="post">
            Name: <input type="text" name="name" value="<?php echo $snip->name; ?>" /> <br/><br/>
            Language:
            <select name="lang">

                <?php $this->langDrop($snip->lang); ?>

            </select>
            <br/><br/>
            Code: <br/>
            <div id="editArea">
            <textarea id="snippet" name="snippet" rows="10" cols="60" class="codepress <?php echo strtolower($this->langByIdCodepress($snip->lang)); ?>" style="width:100%;height:100%;"><?php echo $snip->snippet; ?></textarea>
            </div><br/><br/>
            <input type="submit" class="submit-btn" value="<?php echo $titleType; ?>" />
        </form>
        <br style="clear:both" />
    </div>
</div>