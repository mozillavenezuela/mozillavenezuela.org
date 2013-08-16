<p>Select a folder to import. The folder name will be used as the title of the gallery.</p>
<div id="file_browser">
</div>
<p>
    <input type="button" id="import_button" name="import_folder" value="Import Folder" class="button-primary"/>
</p>
<script type="text/javascript">
    var selected_folder = null;
    jQuery(function($){
        // Only run this function once!
        if (typeof($(window).data('ready')) == 'undefined')
            $(window).data('ready', true);
        else return;

        // Render file browser
        $('#file_browser').fileTree({
            root:           '/',
            script:         photocrati_ajax.url,
            post_params:    {action: 'browse_folder', token: ''}
        }, function(file){
            selected_folder = file;
            $('#file_browser a').each(function(){
                $(this).removeClass('selected_folder');
            })
            $('#file_browser a[rel="'+file+'"]').addClass('selected_folder');
            file = file.split("/");
            file.pop();
            file = '/'+file.pop();
            $('#import_button').val("Import "+file);
        });

        // Import the folder
        $('#import_button').click(function(e){
            e.preventDefault();

            // Show progress bar
            var progress_bar =  $.nggProgressBar({
                title: "Importing gallery",
                infinite: true,
                starting_value: 'In Progress...'
            });

            // Start importing process
            var post_params = {
                action: 'import_folder',
                folder: selected_folder
            };
            $.post(photocrati_ajax.url, post_params, function(response){
                if (typeof(response) != 'object') response = JSON.parse(response);
                if (typeof(response.error) == 'string') {
                    progress_bar.set("Error occurred");
                    alert(response.error);
                }
                else {
                    progress_bar.set('Done! Successfully imported '+response.image_ids.length+' images.');
                }
                progress_bar.close();
            });
        })
    });
</script>