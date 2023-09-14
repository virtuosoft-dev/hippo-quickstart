<?php
/**
 * Quickstart plugin adds the Quickstart tab for an easy-to-use guide
 * and quick website setup.
 * 
 * @version 1.0.0
 * @license GPL-3.0
 * @link https://github.com/steveorevo/hcpp-quickstart
 * 
 */

 if ( ! class_exists( 'Quickstart') ) {
    class Quickstart {
        /**
         * Constructor, listen for the render events
         */
        public function __construct() {
            global $hcpp;
            $hcpp->quickstart = $this;
            $hcpp->add_action( 'hcpp_render_body', [ $this, 'hcpp_render_body' ] );
            $hcpp->add_action( 'hcpp_render_panel', [ $this, 'hcpp_render_panel' ] );
            $hcpp->add_action( 'hcpp_head', [ $this, 'hcpp_head' ] );
        }

        // Redirect to quickstart on login
        public function hcpp_head( $args ) {
            if ( !isset( $_GET['alt'] ) ) return $args;
            $content = $args['content'];
            if ( strpos( $content, 'LOGIN') === false ) return $args;
            $_SESSION['request_uri'] = '/list/web/?quickstart=main';
            return $args;
        }

        // Render the Quickstart body
        public function hcpp_render_body( $args ) {
            if ( !isset( $_GET['quickstart'] ) ) return $args;
            $content = $args['content'];
            global $hcpp;
            $footer = '<footer ' . $hcpp->delLeftMost( $content, '<footer ');
            switch ( $_GET['quickstart'] ) {
                default:
                    $content = file_get_contents( __DIR__ . '/pages/main.tpl' );
            }
            $args['content'] = $content . $footer;
            return $args;
        }

        // Render the Quickstart tab
        public function hcpp_render_panel( $args ) {
            $content = $args['content'];
            if ( !str_contains( $content, '<!-- Web tab -->' ) ) return $args;
            if ( str_contains( $content, 'class="top-bar-menu-link" href="/edit/user/?user=admin&' ) ) return $args;
            
            global $hcpp;
            $before = $hcpp->getLeftMost( $content, '<!-- Web tab -->');
            $after = '<!-- Web tab -->' . $hcpp->delLeftMost( $content, '<!-- Web tab -->');
            $active = isset($_GET['quickstart']) ? ' active' : '';
            if ( $active != '' ) {
                $after = str_replace( 'class="main-menu-item-link active"', 'class="main-menu-item-link"', $after);
            }
            $qs_tab = '<!-- Quickstart tab -->
            <li class="main-menu-item">
                <a class="main-menu-item-link' . $active . '" href="/list/web/?quickstart=main" title="Easy-to-use guide">
                    <p class="main-menu-item-label">QUICKSTART<i class="fas fa-flag-checkered"></i></p>
                    <ul class="main-menu-stats">
                        <li> easy-to-use guide</li>
                    </ul>
                </a>
            </li>';

            // Default to quickstart if logo is clicked
            $before = str_replace( '<a href="/" class="top-bar-logo"', '<a href="https://code.gdn/pws" target="_blank" class="top-bar-logo"', $before);
            
            // Customize help link
            $before = str_replace( 'href="https://hestiacp.com/docs/"', 'href="https://code.gdn/pws/support/"', $before );

            $content = $before . $qs_tab . $after;
            $args['content'] = $content;
            return $args;
        }
    }
    new Quickstart();
}


                