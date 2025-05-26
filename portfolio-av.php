<?php
/*
Plugin Name: Mi Portfolio
Description: Plugin personalizado para gestionar elementos de portfolio con subida de video.
Version: 1.1
Author: Tu Nombre
*/

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
    'supports' => array('title', 'editor', 'thumbnail'),
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

// HTML del metabox
function mi_portfolio_video_html($post) {
  $video_url = get_post_meta($post->ID, '_mi_portfolio_video', true);
  ?>
  <div>
    <input type="text" name="mi_portfolio_video" id="mi_portfolio_video" value="<?php echo esc_attr($video_url); ?>" style="width: 80%;" />
    <button type="button" class="button" id="mi_portfolio_video_button">Subir / Seleccionar Video</button>
  </div>
  <script>
    jQuery(document).ready(function($){
      $('#mi_portfolio_video_button').click(function(e){
        e.preventDefault();
        var custom_uploader = wp.media({
          title: 'Seleccionar video',
          button: {
            text: 'Usar este video'
          },
          library: {
            type: 'video'
          },
          multiple: false
        })
        .on('select', function() {
          var attachment = custom_uploader.state().get('selection').first().toJSON();
          $('#mi_portfolio_video').val(attachment.url);
        })
        .open();
      });
    });
  </script>
  <?php
}

// 3. GUARDAR EL VIDEO
function mi_portfolio_guardar_metadatos($post_id) {
  if (array_key_exists('mi_portfolio_video', $_POST)) {
    update_post_meta(
      $post_id,
      '_mi_portfolio_video',
      esc_url_raw($_POST['mi_portfolio_video'])
    );
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