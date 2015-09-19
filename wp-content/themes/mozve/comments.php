<?php // Do not delete these lines
  if (!empty($_SERVER['SCRIPT_FILENAME']) && 'comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
    die ('Please do not load this page directly. Thanks!');

  /* This variable is for alternating comment background */
  $oddcomment = 'alt';
?>
<?php /* You can start editing here. */ ?>

<?php if ( have_comments() || comments_open() || pings_open() ) : // If there are comments OR comments are open OR pings are open ?>

<section id="comments">
<?php if ( post_password_required() ) : ?>
  <p class="nopassword"><?php _e( 'This post is password protected. Enter the password to view any comments.', 'onemozilla' ); ?></p>
</section><!-- #comments -->
<?php
    /* Stop the rest of comments.php from being processed,
     * but don't kill the script entirely -- we still have
     * to fully load the template.
     */
    return;
  endif;
?>

  <header class="comments-head">
    <h2><?php if($comment_count > 0) { printf(_n( '1 Comentario', 'Št. Comentarios: %d', $comment_count, 'onemozilla'), $comment_count); } else { _e('Sin comentarios'); } ?></h2>
    <?php if (comments_open()) : ?><p class="cmt-post"><a href="#respond"><?php _e('Post a comment','onemozilla'); ?></a></p><?php endif; ?>
  </header>

<?php if ( have_comments() ) : // If there are comments ?>
  <ol id="comment-list" class="hfeed <?php if (get_option('show_avatars')) echo 'av'; // provides a style hook when avatars are enabled ?>">
  <?php wp_list_comments('type=all&style=ol&callback=onemozilla_comment'); // Comment template is in functions.php ?>
  </ol>

  <?php if ( get_comment_pages_count() > 1 ) : // If comment paging is enabled and there are enough comments to paginate, show the comment paging ?>
    <p class="pages"><?php _e('More comments:', 'onemozilla'); paginate_comments_links(); ?></p>
  <?php endif; ?>

<?php endif; ?>

<?php if ( !comments_open() && pings_open() ) : // If comments are closed but pings are open ?>
  <p class="comments-closed pings-open">
    <?php
    /* L10N: 'trackbacks' are when another website refers to this blog post with a link notification */
    printf( __( 'Los comentarios han sido cerrados para este artículo', 'onemozilla' ), get_trackback_url() ); ?>
  </p>
<?php endif; ?>

<?php if (comments_open()) : ?>

  <div id="respond">
  <?php if ( get_option('comment_registration') ) : // If registration is required and you're not logged in, show a message ?>

    <p><?php printf( __('Usted debe estar  <a href="%s">conectado</a> para comentar.', 'onemozilla'), esc_attr(wp_login_url(get_permalink())) ); ?></p>

  <?php else : // else show the form ?>
    <form id="comment-form" action="<?php echo esc_attr(get_option('siteurl')); ?>/wp-comments-post.php" method="post">
      <fieldset>
        <legend><span><?php comment_form_title( __('Deja un comentario'), __('Responder a %s' ) ); ?></span></legend>
        <p id="cancel-comment-reply"><?php cancel_comment_reply_link('Cancelar respuesta'); ?></p>
        <ol>
        <?php if ( $user_ID ) : ?>
          <li class="self"><?php printf( __( 'Estás conectado como <a href="%1$s">%2$s</a>. <a class="logout" href="%3$s">Desea salir?</a>', 'onemozilla' ), admin_url( 'profile.php' ), esc_html($user_identity), wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ); ?></li>
        <?php else : ?>
          <li id="cmt-name">
            <label for="author"><?php _e('Name', 'onemozilla'); ?> <?php if ($req) : ?><span class="note"><?php _e('(required)', 'onemozilla'); ?></span><?php endif; ?></label>
            <input type="text" name="author" id="author" value="<?php echo esc_attr($comment_author); ?>" size="25" <?php if ($req) echo "required aria-required='true'"; ?>>
          </li>
          <li id="cmt-email">
            <label for="email"><?php _e('E-mail', 'onemozilla'); ?> <?php if ($req) : ?><span class="note"><?php _e('(required, will not be published)', 'onemozilla'); ?></span><?php endif; ?></label>
            <input type="email" name="email" id="email" value="<?php echo esc_attr($comment_author_email); ?>" size="25" <?php if ($req) echo "required aria-required='true'"; ?>>
          </li>
          <li id="cmt-web">
            <label for="url"><?php _e('Website', 'onemozilla'); ?></label>
            <input type="url" name="url" id="url" value="<?php echo esc_attr($comment_author_url); ?>" size="25">
          </li>
        <?php endif; ?>
          <li id="cmt-cmt"><label for="comment"><?php _e('Your comment', 'onemozilla'); ?></label> <textarea name="comment" id="comment" cols="50" rows="10"></textarea></li>
          <li id="comment-submit"><button name="submit" class="button-blue" type="submit"><?php _e('Submit Comment', 'onemozilla'); ?></button>
          <?php comment_id_fields(); ?>
          <?php do_action('comment_form', $post->ID); ?></li>
        </ol>
      </fieldset>
    </form>
  <?php endif; // end if reg required and not logged in ?>
  </div><?php // end #respond ?>

  <?php if (get_option('require_name_email')) :
    wp_enqueue_script('fc-checkcomment', get_template_directory_uri() . '/js/fc-checkcomment.js');
    wp_localize_script('fc-checkcomment', 'objectL10n', array(
      'nonameemail' => __('Debe proporcionar un nombre y correo electrónico (su dirección de correo electrónico no será publicada).'),
      'noname' => __('Debe proporcionar un nombre.'),
      'noemail' => __('Debe proporcionar una dirección de correo electrónico (no se publicará).'),
      'bademail' => __('La dirección de correo electrónico que ha introducido no se ve como una dirección de correo electrónico completa. Ejemplo: "yourname@example.com".'),
      'nocomment' => __('Debe escribir un comentario.')
    ) );
  ?>
  <script type="text/javascript">jQuery("#comment-form").submit(function() { return fc_checkform(<?php if ($req) : echo "'req'"; endif; ?>); });</script>
  <?php endif; ?>
<?php endif; // end if comments open ?>

</section><?php // end #comments ?>

<?php endif; // if you delete this the sky will fall on your head ?>
