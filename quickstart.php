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
        }

        // Render the Quickstart body
        public function hcpp_render_body( $args ) {
            if ( !$_GET['quickstart'] == 'true' ) return $args;
            $content = $args['content'];
            global $hcpp;
            $footer = '<footer ' . $hcpp->delLeftMost( $content, '<footer ');
            $content = '<div class="toolbar">
                            <div class="toolbar-inner">
                                <div class="toolbar-buttons">
                                    <a class="button button-secondary button-back js-button-back" href="/list/web/" style="display: none;">
                                        <i class="fas fa-arrow-left icon-blue"></i>Back			
                                    </a>
                                </div>
                                <div class="toolbar-buttons">
                                    <a href="#" class="button">
                                        <i class="fas fa-arrow-right icon-blue"></i>Continue
                                    </a>         
                                </div>
                            </div>
                        </div>
                        <div class="body-reset container" style="min-height: 300px;">
                            <div>
                                <h1>CodeGarden makes it easy to create websites.</h1>
                                <p>Choose an option &amp; click the "Continue" button:</p>
                                <br>
                                <input type="radio" id="qs_create" name="qs_action" value="QS_CREATE">
                                <label for="qs_create">Create a new website.</label><br>
                                <input type="radio" id="qs_edit" name="qs_action" value="QS_EDIT">
                                <label for="qs_edit">Remove or copy a website.</label><br>
                                <input type="radio" id="qs_ie" name="qs_action" value="QS_IE">
                                <label for="qs_ie">Import or export a website.</label>
                            </div>
                        </div>';
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
            $active = $_GET['quickstart'] ? ' active' : '';
            if ( $active != '' ) {
                $after = str_replace( 'class="main-menu-item-link active"', 'class="main-menu-item-link"', $after);
            }
            $qs_tab = '<!-- Quickstart tab -->
            <li class="main-menu-item">
                <a class="main-menu-item-link' . $active . '" href="/list/web/?quickstart=true" title="Easy-to-use guide">
                    <p class="main-menu-item-label">QUICKSTART<i class="fas fa-flag-checkered"></i></p>
                    <ul class="main-menu-stats">
                        <li> easy-to-use guide</li>
                    </ul>
                </a>
            </li>';

            // Default to quickstart if logo is clicked
            $before = str_replace( '<a href="/" class="top-bar-logo"', '<a href="/list/web/?quickstart=true" class="top-bar-logo"', $before);
            $content = $before . $qs_tab . $after;
            $args['content'] = $content;
            return $args;
        }
    }
    new Quickstart();
}


                