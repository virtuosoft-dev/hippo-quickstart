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
            $hcpp->add_action( 'hcpp_render_page', [ $this, 'hcpp_render_page' ] );
        }

        // Render the Quickstart tab
        public function hcpp_render_page( $args ) {
            $content = $args['content'];
            if ( !str_contains( $content, '<!-- Begin toolbar -->') ) return $args;
            if ( !str_contains( $content, '<li class="main-menu-item">' ) ) return $args;
            global $hcpp;
            $hcpp->log("Quickstart: Adding Quickstart tab");
            $before = $hcpp->getLeftMost( $content, '<li class="main-menu-item">') . '<li class="main-menu-item">';
            $after = $hcpp->delLeftMost( $content, '<li class="main-menu-item">');
            $qs_tab = '<!-- Quickstart tab -->
            <li class="main-menu-item">
                <a class="main-menu-item-link active" href="/list/web/?quickstart=true" title="Easy-to-use guide">
                    <p class="main-menu-item-label">QUICKSTART<i class="fas fa-flag-checkered"></i></p>
                    <ul class="main-menu-stats">
                        <li> easy-to-use </li>
                        <li> guide </li>
                    </ul>
                </a>
            </li>';
            $content = $before . $qs_tab . $after;
            $args['content'] = $content;
            return $args;
        }
    }
    new Quickstart();
}

// TODO: QUICKSTART Wizard
        // <!-- Quickstart tab -->
        // <li class="main-menu-item">
        //     <a class="main-menu-item-link active" href="/list/web/?quickstart=true" title="Easy-to-use guide">
        //         <p class="main-menu-item-label">QUICKSTART<i class="fas fa-flag-checkered"></i></p>
        //         <ul class="main-menu-stats">
        //             <li> easy-to-use </li>
        //             <li> guide </li>
        //         </ul>
        //     </a>
        // </li>