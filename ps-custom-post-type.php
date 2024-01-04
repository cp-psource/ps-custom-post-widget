<?php
/*
Plugin Name: PS Custom Posts Type Widget
Plugin URI: https://n3rds.work/piestingtal_source/ps-custom-post-widget/
Description: Ermöglicht die Anzeige von benutzerdefinierten Beitragstypen und normalen Beiträgen mit Beitragsbildern und Auszügen als Widget.
Version: 1.0.3
Author: WMS N@W
Author URI: https://n3rds.work


Copyright 2020 WMS N@W (https://n3rds.work)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
require 'psource/psource-plugin-update/psource-plugin-updater.php';
$MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://n3rds.work//wp-update-server/?action=get_metadata&slug=ps-custom-post-widget', 
	__FILE__, 
	'ps-custom-post-widget' 
);

///////////////////////////////////////////////////////////////////////////



class RcptWidget extends WP_Widget {

    private $_order_options = array();
    private $_order_directions = array();

    public function __construct() {
        $this->_order_options    = array(
            'none'     => __( 'Nichts', 'rcpt' ),
            'rand'     => __( 'Zufällig', 'rcpt' ),
            'id'       => __( 'Nach ID', 'rcpt' ),
            'author'   => __( 'Nach Autor', 'rcpt' ),
            'title'    => __( 'Nach Titel', 'rcpt' ),
            'date'     => __( 'Veröffentlichung', 'rcpt' ),
            'modified' => __( 'Zuletzt aktualisiert', 'rcpt' ),
        );
        $this->_order_directions = array(
            'ASC'  => __( 'Aufsteigend', 'rcpt' ),
            'DESC' => __( 'Absteigend', 'rcpt' ),
        );

        parent::__construct(
            'RcptWidget',
            __( 'Benutzerdefinierte Beiträge Widget', 'rcpt' ),
            array(
                'classname'   => 'widget_rcpt',
                'description' => __( 'Ermöglicht die Anzeige von benutzerdefinierten Beitragstypen und normalen Beiträgen mit Beitragsbildern und Auszügen.', 'rcpt' ),
            )
        );
    }

    public function form( $instance ) {
        // Sicherheitsmaßnahmen für Daten
        $title          = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
        $post_type      = isset( $instance['post_type'] ) ? esc_attr( $instance['post_type'] ) : '';
        $featured_image = isset( $instance['featured_image'] ) ? esc_attr( $instance['featured_image'] ) : '';
        $post_author    = isset( $instance['post_author'] ) ? esc_attr( $instance['post_author'] ) : '';
        $limit          = isset( $instance['limit'] ) ? esc_attr( $instance['limit'] ) : '';
        $class          = isset( $instance['class'] ) ? esc_attr( $instance['class'] ) : '';
        $order_by       = isset( $instance['order_by'] ) ? esc_attr( $instance['order_by'] ) : '';
        $order_dir      = isset( $instance['order_dir'] ) ? esc_attr( $instance['order_dir'] ) : '';

		// Fields
		$show_title      = !isset( $instance['show_title'] ) ? (int) $instance['show_title'] : true; // Show by default
		$titles_as_links = !isset( $instance['titles_as_links'] ) ? (int) $instance['titles_as_links'] : true; // True by default
		$show_body       = (int) $instance['show_body'];
		$show_thumbs     = esc_attr( $instance['show_thumbs'] );
		$show_dates      = esc_attr( $instance['show_dates'] );

		$fields = $instance['fields'];
		$fields = $fields ? $fields : array();

		// Set defaults
		// ...

		// Get post types
		$post_types   = get_post_types( array( 'public' => true ), 'objects' );
		$post_authors = $this->_get_post_authors();

		$html = '<p>';
        $html .= '<label for="' . esc_attr( $this->get_field_id( 'title' ) ) . '">' . esc_html__( 'Titel:', 'rcpt' ) . '</label>';
        $html .= '<input type="text" name="' . esc_attr( $this->get_field_name( 'title' ) ) . '" id="' . esc_attr( $this->get_field_id( 'title' ) ) . '" class="widefat" value="' . $title . '"/>';
        $html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'post_type' ) . '">' . __( 'Beitragstyp:', 'rcpt' ) . '</label>';
		$html .= '<select name="' . $this->get_field_name( 'post_type' ) . '" id="' . $this->get_field_id( 'post_type' ) . '">';
		foreach ( $post_types as $pt ) {
			$html .= '<option value="' . $pt->name . '" ' . ( ( $pt->name == $post_type ) ? 'selected="selected"' : '' ) . '>' . $pt->label . '</option>';
		}
		$html .= '</select>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'post_author' ) . '">' . __( 'Verfasst von:', 'rcpt' ) . '</label>';
		$html .= '<select name="' . $this->get_field_name( 'post_author' ) . '" id="' . $this->get_field_id( 'post_author' ) . '">';
		foreach ( $post_authors as $pa_id => $pa_name ) {
			$html .= '<option value="' . $pa_id . '" ' . ( ( $pa_id == $post_author ) ? 'selected="selected"' : '' ) . '>' . $pa_name . '&nbsp;</option>';
		}
		$html .= '</select>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'limit' ) . '">' . __( 'Limit:', 'rcpt' ) . '</label>';
		$html .= '<select name="' . $this->get_field_name( 'limit' ) . '" id="' . $this->get_field_id( 'limit' ) . '">';
		for ( $i = 1; $i < 21; $i ++ ) {
			$html .= '<option value="' . $i . '" ' . ( ( $i == $limit ) ? 'selected="selected"' : '' ) . '>' . $i . '</option>';
		}
		$html .= '</select>';
		$html .= '</p>';

		$html .= '<p>' .
            '<input type="checkbox" name="' . esc_attr( $this->get_field_name( 'featured_image' ) ) . '" id="' . esc_attr( $this->get_field_id( 'featured_image' ) ) . '" value="1" ' . checked( 1, $featured_image, false ) . '/>' .
            ' <label for="' . esc_attr( $this->get_field_id( 'featured_image' ) ) . '">' . esc_html__( 'Beschränke auf Beiträge mit Beitragsbildern', 'rcpt' ) . '</label> ' .
            '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'class' ) . '">' . __( 'Zusätzliche CSS Klasse(n) <small>(optional)</small>:', 'rcpt' ) . '</label>';
		$html .= '<input type="text" name="' . $this->get_field_name( 'class' ) . '" id="' . $this->get_field_id( 'class' ) . '" class="widefat" value="' . $class . '"/>';
		$html .= '<div><small>' . __( 'Ein oder mehrere durch Leerzeichen getrennte gültige CSS-Klassennamen, die auf die generierte Liste angewendet werden', 'rcpt' ) . '</small></div>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'order_by' ) . '">' . __( 'Sortieren nach:', 'rcpt' ) . '</label>';
		$html .= '<select name="' . $this->get_field_name( 'order_by' ) . '" id="' . $this->get_field_id( 'order_by' ) . '">';
		foreach ( $this->_order_options as $key => $label ) {
			$html .= '<option value="' . $key . '" ' . ( ( $key == $order_by ) ? 'selected="selected"' : '' ) . '>' . __( $label, 'rcpt' ) . '</option>';
		}
		$html .= '</select>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'order_dir' ) . '">' . __( 'Sortierreihenfolge:', 'rcpt' ) . '</label>';
		$html .= '<select name="' . $this->get_field_name( 'order_dir' ) . '" id="' . $this->get_field_id( 'order_dir' ) . '">';
		foreach ( $this->_order_directions as $key => $label ) {
			$html .= '<option value="' . $key . '" ' . ( ( $key == $order_dir ) ? 'selected="selected"' : '' ) . '>' . __( $label, 'rcpt' ) . '</option>';
		}
		$html .= '</select>';
		$html .= '</p>';

		$html .= '<p>' .
            '<input type="checkbox" name="' . esc_attr( $this->get_field_name( 'show_title' ) ) . '" id="' . esc_attr( $this->get_field_id( 'show_title' ) ) . '" value="1" ' . checked( 1, $show_title, false ) . '/>' .
            ' <label for="' . esc_attr( $this->get_field_id( 'show_title' ) ) . '">' . esc_html__( 'Titel zeigen', 'rcpt' ) . '</label> ' .
            '</p>';

        $html .= '<p>&nbsp;&nbsp;&nbsp;&nbsp;<small>' .
            '<input type="checkbox" name="' . esc_attr( $this->get_field_name( 'titles_as_links' ) ) . '" id="' . esc_attr( $this->get_field_id( 'titles_as_links' ) ) . '" value="1" ' . checked( 1, $titles_as_links, false ) . '/>' .
            ' <label for="' . esc_attr( $this->get_field_id( 'titles_as_links' ) ) . '">' . esc_html__( 'Titel und Links zu Beiträgen', 'rcpt' ) . '</label> ' .
            '</small></p>';

			$html .= '<p>' .
            '<input type="checkbox" name="' . esc_attr( $this->get_field_name( 'show_body' ) ) . '" id="' . esc_attr( $this->get_field_id( 'show_body' ) ) . '" value="1" ' . checked( 1, $show_body, false ) . '/>' .
            ' <label for="' . esc_attr( $this->get_field_id( 'show_body' ) ) . '">' . esc_html__( 'Auszüge zeigen', 'rcpt' ) . '</label> ' .
            '</p>';

        $html .= '<p>' .
            '<input type="checkbox" name="' . esc_attr( $this->get_field_name( 'show_dates' ) ) . '" id="' . esc_attr( $this->get_field_id( 'show_dates' ) ) . '" value="1" ' . checked( 1, $show_dates, false ) . '/>' .
            ' <label for="' . esc_attr( $this->get_field_id( 'show_dates' ) ) . '">' . esc_html__( 'Veröffentlichungsdatum zeigen', 'rcpt' ) . '</label> ' .
            '</p>';

        $html .= '<p>' .
            '<input type="checkbox" name="' . esc_attr( $this->get_field_name( 'show_thumbs' ) ) . '" id="' . esc_attr( $this->get_field_id( 'show_thumbs' ) ) . '" value="1" ' . checked( 1, $show_thumbs, false ) . '/>' .
            ' <label for="' . esc_attr( $this->get_field_id( 'show_thumbs' ) ) . '">' . esc_html__( 'Beitragbilder zeigen <small>(falls verfügbar)</small>', 'rcpt' ) . '</label> ' .
            '</p>';

		// Custom fields
        $id = sprintf( 'rcpt-custom_fields-%04d-%04d-%04d-%04d', rand(), rand(), rand(), rand() );
        $html .= '<p><a href="#toggle" class="rcpt-toggle_custom_fields" id="' . esc_attr( $id ) . '-handler">' . esc_html__( 'Zeige/Verberge Benutzerdefinierte Felder', 'rcpt' ) . '</a></p>';
        $html .= '<div class="rcpt-show_custom_fields" id="' . esc_attr( $id ) . '" style="display:none">';
        $html .= '<h5>' . esc_html__( 'Benutzerdefinierte Felder', 'rcpt' ) . '</h5>';
        $html .= '<p>';
        $_fields      = $this->_get_post_fields( $post_type );
        $shown_fields = array();
        $skips        = array(
            '/^_edit_.*/',
            '/^_thumbnail_.*/',
            '/^_wp_.*/',
        );
        if ( $_fields ) {
            foreach ( $_fields as $field ) {
                if ( preg_filter( $skips, ':skip:', $field ) ) {
                    continue;
                }
                $value          = in_array( $field, array_keys( $fields ) ) ? esc_attr( $fields[ $field ] ) : '';
                $shown_fields[] = '<label for="' . esc_attr( $this->get_field_id( 'fields' ) ) . '-' . $field . '">' . sprintf( esc_html__( 'Etikett für &quot;%s&quot;', 'rcpt' ), $field ) . '</label>' .
                    '<input type="text" class="widefat" name="' . esc_attr( $this->get_field_name( 'fields' ) ) . '[' . $field . ']" id="' . esc_attr( $this->get_field_id( 'fields' ) ) . '-' . $field . '" value="' . $value . '" />' .
                '';
            }
        }
        if ( $shown_fields ) {
            $html .= '<small><em>' . esc_html__( "Felder ohne zugehörige Bezeichnung werden in der Widget-Ausgabe nicht angezeigt", 'rcpt' ) . '</em></small><br />';
            $html .= join( '<br />', $shown_fields );
        }
        $html .= '<small><em>' . esc_html__( 'Wähle einen Beitragstyp aus und speichere die Einstellungen, um sie zu aktualisieren', 'rcpt' ) . '</em></small>';
        $html .= '</p>';
        $html .= '</div>';
        $html .= <<<EORcptJs
		<script type="text/javascript">
		(function ($) {
		$("#{$id}-handler").on("click", function () {
			var el = $("#{$id}");
			if (!el.length) return false;
			if (el.is(":visible")) el.hide();
			else el.show();
			return false;
		});
		})(jQuery);
		</script>
		EORcptJs;

        echo $html;
    }

	private function _get_post_fields( $type ) {
		if ( ! $type ) {
			return false;
		}
		global $wpdb;
		$fields = $wpdb->get_col(
			$wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta}, {$wpdb->posts} WHERE post_id=ID and post_type='%s'", $type )
		);

		return $fields;
	}

	private function _get_post_authors() {
		global $wpdb;
		$authors = $wpdb->get_col( "SELECT DISTINCT post_author FROM {$wpdb->posts}" );
		$info    = array( '' => __( 'Jeder', 'rcpt' ) );
		foreach ( $authors as $author ) {
			$user            = new WP_User( $author );
			$info[ $author ] = $user->display_name;
		}

		return $info;
	}

	public function update( $new_instance, $old_instance ) {
        $instance                   = $old_instance;
        $instance['title']          = sanitize_text_field( $new_instance['title'] );
        $instance['post_type']      = sanitize_text_field( $new_instance['post_type'] );
        $instance['featured_image'] = isset( $new_instance['featured_image'] ) ? 1 : 0;
        $instance['post_author']    = absint( $new_instance['post_author'] );
        $instance['limit']          = absint( $new_instance['limit'] );
        $instance['class']          = sanitize_text_field( $new_instance['class'] );
        $instance['order_by']       = sanitize_text_field( $new_instance['order_by'] );
        $instance['order_dir']      = sanitize_text_field( $new_instance['order_dir'] );

        $instance['show_title']      = isset( $new_instance['show_title'] ) ? 1 : 0;
        $instance['titles_as_links'] = isset( $new_instance['titles_as_links'] ) ? 1 : 0;
        $instance['show_body']       = isset( $new_instance['show_body'] ) ? 1 : 0;
        $instance['show_thumbs']     = isset( $new_instance['show_thumbs'] ) ? 1 : 0;
        $instance['show_dates']      = isset( $new_instance['show_dates'] ) ? 1 : 0;

        $instance['fields'] = array();
        $fields             = $new_instance['fields'];
        $fields             = $fields ? $fields : array();
        foreach ( $fields as $key => $value ) {
            $key                        = sanitize_text_field( $key );
            $instance['fields'][ $key ] = sanitize_text_field( $value );
        }

        return $instance;
    }

	public function widget( $args, $instance ) {
        extract( $args );
        $title          = apply_filters( 'widget_title', sanitize_text_field( $instance['title'] ) );
        $post_type      = sanitize_text_field( $instance['post_type'] );
        $post_author    = absint( $instance['post_author'] );
        $featured_image = $instance['featured_image'] ? true : false;
        $limit          = absint( $instance['limit'] );
        $class          = sanitize_text_field( $instance['class'] );
        $class          = $class ? " {$class}" : '';

        $order_by  = sanitize_text_field( $instance['order_by'] );
        $order_by  = array_key_exists( $order_by, $this->_order_options ) ? $order_by : 'none';
        $order_dir = sanitize_text_field( $instance['order_dir'] );
        $order_dir = array_key_exists( $order_dir, $this->_order_directions ) ? $order_dir : 'ASC';

        // Fields
        $show_title      = ! empty( $instance['show_title'] ) ? true : false;
        $titles_as_links = ! empty( $instance['titles_as_links'] ) ? true : false;
        $show_body       = ! empty( $instance['show_body'] ) ? true : false;
        $show_thumbs     = ! empty( $instance['show_thumbs'] ) ? true : false;
        $show_dates      = ! empty( $instance['show_dates'] ) ? true : false;

        $fields = $instance['fields'];
        $fields = $fields ? $fields : array();

        $query_args = array(
            'showposts'          => $limit,
            'nopaging'           => 0,
            'post_status'        => 'publish',
            'post_type'          => $post_type,
            'orderby'            => $order_by,
            'order'              => $order_dir,
            'ignore_sticky_posts' => 1
        );
        if ( $post_author ) {
            $query_args['author'] = $post_author;
        }
        if ( $featured_image ) {
            $query_args['meta_key'] = '_thumbnail_id';
        }
        $query = new WP_Query( $query_args );

        if ( $query->have_posts() ) {
            echo $before_widget;
            if ( $title ) {
                echo $before_title . esc_html( $title ) . $after_title;
            }

            while ( $query->have_posts() ) {
                $query->the_post();

                $item_title = get_the_title() ? esc_html( get_the_title() ) : get_the_ID();
                $image      = $src = $width = $height = false;
                if ( $show_thumbs ) {
                    $thumb_id = get_post_thumbnail_id( get_the_ID() );
                    if ( $thumb_id ) {
                        $image = wp_get_attachment_image_src( $thumb_id, 'large' );
                        if ( $image ) {
                            $src    = esc_url( $image[0] );
                            $width  = esc_attr( $image[1] );
                            $height = esc_attr( $image[2] );
                        }
                    }
                }

                $image_format = $image
                    ? '<span class="rcpt_item_image"><img src="%s" height="%d" width="%d" alt="%s" border="0" /></span>'
                    : '';
                $image_str    = sprintf( $image_format, $src, $height, $width, esc_html( $item_title ) );

                $item_title_str       = $show_title
                    ? sprintf( '<span class="rcpt_item_title">%s %s</span>', $image_str, esc_html( $item_title ) )
                    : sprintf( '<span class="rcpt_item_title">%s</span>', $image_str );
                $final_item_title_str = $titles_as_links
                    ? sprintf( '<a href="%s" title="%s">%s</a>', esc_url( get_permalink() ), esc_attr( $item_title ), $item_title_str )
                    : $item_title_str;

                $post_fields  = get_post_custom( get_the_ID() );
                $shown_fields = array();
                foreach ( $post_fields as $field => $value ) {
                    if ( ! in_array( $field, array_keys( $fields ) ) ) {
                        continue;
                    } // Not here
                    if ( ! $fields[ $field ] ) {
                        continue;
                    } // No label
                    $value          = is_array( $value ) ? join( ', ', $value ) : $value;
                    $shown_fields[] = array(
                        'label' => wp_strip_all_tags( $fields[ $field ] ),
                        'value' => wp_strip_all_tags( $value ),
                    );
                }

                echo '<div class="rcpt_items"><ul class="rcpt_items_list' . esc_attr( $class ) . '">';

                echo '<li>';
                echo $final_item_title_str;
                if ( $show_body ) {
                    echo '<div class="rcpt_item_excerpt">' . wp_kses_post( get_the_excerpt() ) . '</div>';
                }
                if ( $show_dates ) {
                    echo '<span class="rcpt_item_date"><span class="rcpt_item_posted">' . esc_html__( 'Veröffentlicht am', 'rcpt' ) . ' </span>' . esc_html( get_the_date() ) . '</span>';
                }
                if ( $shown_fields ) {
                    echo '<dl class="rcpt_item_custom_fields">';
                    foreach ( $shown_fields as $custom ) {
                        echo '<dt>' . esc_html( $custom['label'] ) . '</dt><dd>' . esc_html( $custom['value'] ) . '</dd>';
                    }
                    echo '</dl>';
                }
                echo '</li>';

                echo '</ul></div>';
            }
            echo $after_widget;
            wp_reset_postdata();
        }
    }
}

load_plugin_textdomain( 'rcpt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

// Widget registrieren
function Rcpt_init_Widget() {
    return register_widget( 'RcptWidget' );
}

add_action( 'widgets_init', 'Rcpt_init_Widget' );

// Stile für das Widget in die Warteschlange einfügen
if ( ! is_admin() ) {
    function rcpt_style() {
        return wp_enqueue_style( "rcpt_style", plugins_url( "media/style.css", __FILE__ ) );
    }

    add_action( 'wp_enqueue_scripts', 'rcpt_style' );
}