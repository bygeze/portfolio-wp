<?php
/*
Plugin Name: Mi Portfolio
Description: Plugin personalizado para gestionar elementos de portfolio con subida de video.
Version: 1.2
Author: Tu Nombre
*/

function mi_portfolio_enqueue_admin_scripts($hook) {
    global $post;
    // Cargar solo en la pantalla de editar o crear 'portfolio'
    if ( ('post.php' == $hook || 'post-new.php' == $hook) && $post && $post->post_type == 'portfolio' ) {
        wp_enqueue_media(); // importante para cargar wp.media
        wp_enqueue_script('jquery');
    }
}
add_action('admin_enqueue_scripts', 'mi_portfolio_enqueue_admin_scripts');

// 1. REGISTRO DEL CUSTOM POST TYPE
function mi_portfolio_registrar_cpt() {
  $args = array(
    'labels' => array(
      'name' => 'Portfolio',
      'singular_name' => 'Item de Portfolio',
      'add_new' => 'Agregar nuevo',
      'add_new_item' => 'Agregar nuevo item',
      'edit_item' => 'Editar item',
      'new_item' => 'Nuevo item',
      'view_item' => 'Ver item',
      'search_items' => 'Buscar items',
      'not_found' => 'No encontrado',
      'not_found_in_trash' => 'No encontrado en la papelera'
    ),
    'public' => true,
    'has_archive' => true,
    'rewrite' => array('slug' => 'portfolio'),
    'supports' => array('title', 'thumbnail'),
    'menu_icon' => 'dashicons-format-video',
    'show_in_rest' => true
  );
  register_post_type('portfolio', $args);
}
add_action('init', 'mi_portfolio_registrar_cpt');

// 2. AGREGAR METABOX PARA SUBIR VIDEO
function mi_portfolio_agregar_metabox() {
  add_meta_box(
    'mi_portfolio_video',
    'Video del Portfolio',
    'mi_portfolio_video_html',
    'portfolio',
    'normal',
    'default'
  );
}
add_action('add_meta_boxes', 'mi_portfolio_agregar_metabox');

// HTML del metabox con preview y campo oculto para el ID
function mi_portfolio_video_html($post) {
  $video_id = get_post_meta($post->ID, '_mi_portfolio_video', true);
  ?>
  <div>
    <input type="hidden" name="mi_portfolio_video" id="mi_portfolio_video_id" value="<?php echo esc_attr($video_id); ?>" />
    <button type="button" class="button" id="mi_portfolio_video_button">Subir / Seleccionar Video</button>
  </div>
  <div id="mi_portfolio_video_preview" style="margin-top:10px;">
    <?php 
    if ($video_id) {
      $video_url = wp_get_attachment_url($video_id);
      if ($video_url) {
        echo '<video width="320" height="240" controls>';
        echo '<source src="' . esc_url($video_url) . '" type="video/mp4">';
        echo 'Tu navegador no soporta el video.';
        echo '</video>';
      }
    }
    ?>
  </div>

  <script>
    jQuery(document).ready(function($){
      var videoInput = $('#mi_portfolio_video_id');
      var videoPreview = $('#mi_portfolio_video_preview');

      function loadVideoPreview(videoID){
        if(videoID){
          $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
              action: 'mi_portfolio_get_video_url',
              video_id: videoID
            },
            success: function(response){
              if(response.success && response.data.url){
                videoPreview.html('<video width="320" height="240" controls><source src="'+response.data.url+'" type="video/mp4">Tu navegador no soporta el video.</video>');
              } else {
                videoPreview.html('');
              }
            }
          });
        } else {
          videoPreview.html('');
        }
      }

      loadVideoPreview(videoInput.val());
	
		
      var custom_uploader;
      $('#mi_portfolio_video_button').click(function(e){
        e.preventDefault();
        if (custom_uploader) {
          custom_uploader.open();
          return;
        }
        custom_uploader = wp.media({
          title: 'Seleccionar video',
          button: { text: 'Usar este video' },
          library: { type: 'video' },
          multiple: false
        });
        custom_uploader.on('select', function() {
			console.log("item selected");
          var attachment = custom_uploader.state().get('selection').first().toJSON();
          videoInput.val(attachment.id).trigger('change');
          loadVideoPreview(attachment.id);
        });
        custom_uploader.open();
      });
    });
  </script>
  <?php
}

// 3. GUARDAR EL ID DEL VIDEO
function mi_portfolio_guardar_metadatos($post_id) {
  // Evitar guardado automático o revisiones
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (wp_is_post_revision($post_id)) return;
  if (wp_is_post_autosave($post_id)) return;

  // Verificamos que el campo venga y sea entero
  if (isset($_POST['mi_portfolio_video']) && !empty($_POST['mi_portfolio_video'])) {
    $video_id = intval($_POST['mi_portfolio_video']);
    update_post_meta($post_id, '_mi_portfolio_video', $video_id);
  } else {
    // Si no viene valor, borramos el meta para evitar que quede vacío y confundas el preview
    delete_post_meta($post_id, '_mi_portfolio_video');
  }
}
add_action('save_post', 'mi_portfolio_guardar_metadatos');

// 4. PERMITIR CARGA DE VIDEOS (por si está restringido)
function mi_portfolio_permitir_mime_video($mimes) {
  $mimes['mp4'] = 'video/mp4';
  $mimes['webm'] = 'video/webm';
  $mimes['ogg'] = 'video/ogg';
  return $mimes;
}
add_filter('upload_mimes', 'mi_portfolio_permitir_mime_video');

// AJAX para devolver la URL del video según el ID
function mi_portfolio_get_video_url_ajax(){
  if(!empty($_POST['video_id'])){
    $video_id = intval($_POST['video_id']);
    $video_url = wp_get_attachment_url($video_id);
    if($video_url){
      wp_send_json_success(['url' => $video_url]);
    }
  }
  wp_send_json_error();
}
add_action('wp_ajax_mi_portfolio_get_video_url', 'mi_portfolio_get_video_url_ajax');

// Metabox para descripción en texto plano
function mi_portfolio_add_descripcion_metabox() {
  add_meta_box(
    'mi_portfolio_descripcion',
    'Descripción (solo texto)',
    'mi_portfolio_descripcion_callback',
    'portfolio',
    'normal',
    'default'
  );
}
add_action('add_meta_boxes', 'mi_portfolio_add_descripcion_metabox');

function mi_portfolio_descripcion_callback($post) {
  $descripcion = get_post_meta($post->ID, '_mi_portfolio_descripcion', true);
  echo '<textarea name="mi_portfolio_descripcion" rows="5" style="width:100%;">' . esc_textarea($descripcion) . '</textarea>';
}

function mi_portfolio_guardar_descripcion($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (isset($_POST['mi_portfolio_descripcion'])) {
    update_post_meta(
      $post_id,
      '_mi_portfolio_descripcion',
      sanitize_text_field($_POST['mi_portfolio_descripcion'])
    );
  }
}
add_action('save_post', 'mi_portfolio_guardar_descripcion');
