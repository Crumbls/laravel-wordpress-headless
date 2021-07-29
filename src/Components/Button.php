<?php

namespace Crumbls\LaravelDivi\Components;

use Illuminate\View\Component;

class Button extends AbstractElement
{
    public $attributesExtended = [
    ];



    public function prerender() {
        parent::prerender();
        return;
//dd($this->attributes);
        $button_url                      = $this->props['button_url'];
        $button_rel                      = $this->props['button_rel'];
        $button_text                     = $this->_esc_attr( 'button_text', 'limited' );
        $url_new_window                  = $this->props['url_new_window'];
        $button_custom                   = $this->props['custom_button'];

        $button_alignment                = $this->get_button_alignment();
        $is_button_aligment_responsive   = et_pb_responsive_options()->is_responsive_enabled( $this->props, 'button_alignment' );
        $button_alignment_tablet         = $is_button_aligment_responsive ? $this->get_button_alignment( 'tablet' ) : '';
        $button_alignment_phone          = $is_button_aligment_responsive ? $this->get_button_alignment( 'phone' ) : '';

        $background_layout               = $this->props['background_layout'];
        $background_layout_hover         = et_pb_hover_options()->get_value( 'background_layout', $this->props, 'light' );
        $background_layout_hover_enabled = et_pb_hover_options()->is_enabled( 'background_layout', $this->props );
        $background_layout_values        = et_pb_responsive_options()->get_property_values( $this->props, 'background_layout' );
        $background_layout_tablet        = isset( $background_layout_values['tablet'] ) ? $background_layout_values['tablet'] : '';
        $background_layout_phone         = isset( $background_layout_values['phone'] ) ? $background_layout_values['phone'] : '';

        $custom_icon_values              = et_pb_responsive_options()->get_property_values( $this->props, 'button_icon' );
        $custom_icon                     = isset( $custom_icon_values['desktop'] ) ? $custom_icon_values['desktop'] : '';
        $custom_icon_tablet              = isset( $custom_icon_values['tablet'] ) ? $custom_icon_values['tablet'] : '';
        $custom_icon_phone               = isset( $custom_icon_values['phone'] ) ? $custom_icon_values['phone'] : '';

        // Button Alignment.
        $button_alignments = array();
        if ( ! empty( $button_alignment ) ) {
            array_push( $button_alignments, sprintf( 'et_pb_button_alignment_%1$s', esc_attr( $button_alignment ) ) );
        }

        if ( ! empty( $button_alignment_tablet ) ) {
            array_push( $button_alignments, sprintf( 'et_pb_button_alignment_tablet_%1$s', esc_attr( $button_alignment_tablet ) ) );
        }

        if ( ! empty( $button_alignment_phone ) ) {
            array_push( $button_alignments, sprintf( 'et_pb_button_alignment_phone_%1$s', esc_attr( $button_alignment_phone ) ) );
        }

        $button_alignment_classes = join( ' ', $button_alignments );

        // Nothing to output if neither Button Text nor Button URL defined
        $button_url = trim( $button_url );

        if ( '' === $button_text && '' === $button_url ) {
            return '';
        }

        $data_background_layout       = '';
        $data_background_layout_hover = '';
        if ( $background_layout_hover_enabled ) {
            $data_background_layout = sprintf(
                ' data-background-layout="%1$s"',
                esc_attr( $background_layout )
            );
            $data_background_layout_hover = sprintf(
                ' data-background-layout-hover="%1$s"',
                esc_attr( $background_layout_hover )
            );
        }

        // Module classnames
        $this->add_classname( "et_pb_bg_layout_{$background_layout}" );
        if ( ! empty( $background_layout_tablet ) ) {
            $this->add_classname( "et_pb_bg_layout_{$background_layout_tablet}_tablet" );
        }
        if ( ! empty( $background_layout_phone ) ) {
            $this->add_classname( "et_pb_bg_layout_{$background_layout_phone}_phone" );
        }

        $this->remove_classname( 'et_pb_module' );

        // Render Button
        $button = $this->render_button( array(
            'button_id'           => $this->module_id( false ),
            'button_classname'    => explode( ' ', $this->module_classname( $render_slug ) ),
            'button_custom'       => $button_custom,
            'button_rel'          => $button_rel,
            'button_text'         => $button_text,
            'button_text_escaped' => true,
            'button_url'          => $button_url,
            'custom_icon'         => $custom_icon,
            'custom_icon_tablet'  => $custom_icon_tablet,
            'custom_icon_phone'   => $custom_icon_phone,
            'has_wrapper'         => false,
            'url_new_window'      => $url_new_window,
        ) );

        // Render module output
        $output = sprintf(
            '<div class="et_pb_button_module_wrapper et_pb_button_%3$s_wrapper %2$s et_pb_module "%4$s%5$s>
				%1$s
			</div>',
            et_core_esc_previously( $button ),
            esc_attr( $button_alignment_classes ),
            esc_attr( $this->render_count() ),
            et_core_esc_previously( $data_background_layout ),
            et_core_esc_previously( $data_background_layout_hover )
        );

        self::set_style( $render_slug, array(
            'selector'    => '%%order_class%%, %%order_class%%:after',
            'declaration' => esc_html( $this->get_transition_style( array( 'all' ) ) )
        ) );

        return $output;
    }
}
