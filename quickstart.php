<?php
/**
 * Quickstart plugin adds the Quickstart tab for an easy-to-use guide
 * and quick website setup.
 * 
 * @version 1.0.0
 * @license GPL-3.0
 * @link https://github.com/virtuosoft-dev/hcpp-quickstart
 * 
 * TODO: run long actions (export, blueprint download, import, etc.) under user process
 * 
 */
if ( ! class_exists( 'Quickstart') ) {
    class Quickstart {

        /**
         * Cancel the job process by job id and clean up data.
         * @param array $job_id The job id of the process to cancel.
         */
        public function cancel_job( $job_id ) {
            $pid = $this->get_job_data( $job_id, 'pid' );
            global $hcpp;
            $hcpp->run( "invoke-plugin quickstart_cancel_job $job_id $pid" );
            if ( isset( $_SESSION['devstia_jobs'][$job_id] ) ) {
                unset( $_SESSION['devstia_jobs'][$job_id] );
            }
        }

        /**
         * Constructor, listen for the render events
         */
        public function __construct() {
            global $hcpp;
            $hcpp->quickstart = $this;
            $hcpp->add_action( 'hcpp_head', [ $this, 'hcpp_head' ] );
            $hcpp->add_action( 'hcpp_invoke_plugin', [ $this, 'hcpp_invoke_plugin' ] );
            $hcpp->add_action( 'hcpp_rebooted', [ $this, 'hcpp_rebooted' ] );
            $hcpp->add_action( 'hcpp_render_body', [ $this, 'hcpp_render_body' ] );
            $hcpp->add_action( 'hcpp_render_panel', [ $this, 'hcpp_render_panel' ] );
            $hcpp->add_action( 'priv_log_user_logout', [ $this, 'priv_log_user_logout' ] );
            $hcpp->add_action( 'hcpp_plugin_disabled', [ $this, 'hcpp_plugin_disabled' ] );
            $hcpp->add_action( 'hcpp_plugin_enabled', [ $this, 'hcpp_plugin_enabled' ] );
        }

        /**
         * Start the upload server on plugin enabled.
         */
        public function hcpp_plugin_enabled( $plugin ) {
            if ( $plugin !== 'quickstart' ) return $plugin;
            $this->start_upload_server();
            return $plugin;
        }

        /**
         * Stop the upload server on plugin disabled.
         */
        public function hcpp_plugin_disabled( $plugin ) {
            if ( $plugin !== 'quickstart' ) return $plugin;
            $this->stop_upload_server();
            return $plugin;
        }

        /**
         * Start the upload server.
         */
        public function start_upload_server() {
            global $hcpp;
            return $hcpp->run( "invoke-plugin quickstart_start_upload_server" );
        }

        /**
         * Stop the upload server.
         */
        public function stop_upload_server() {
            global $hcpp;
            return $hcpp->run( "invoke-plugin quickstart_stop_upload_server" );
        }

        /**
         * Start the upload server as admin; check for any existing server and start if not.
         */
        public function quickstart_start_upload_server() {
            global $hcpp;
            $cmd = '/usr/local/hestia/plugins/quickstart/start-upload-server.sh';
            $cmd = $hcpp->do_action( 'quickstart_start_upload_server', $cmd );
            $hcpp->log( shell_exec( $cmd ) );
        }

        /**
         * Stop the upload server.
         */
        public function quickstart_stop_upload_server() {
            global $hcpp;
            $cmd = '/usr/local/hestia/plugins/quickstart/stop-upload-server.sh';
            $cmd = $hcpp->do_action( 'quickstart_stop_upload_server', $cmd );
            $hcpp->log( shell_exec( $cmd ) );
        }

        /**
         * Download the given blueprint file to our the job id.
         */
        public function blueprint_file( $job_id, $url ) {
            $this->set_job_data( $job_id, 'url', $url );
            $this->set_job_data( $job_id, 'user', $_SESSION['user'] );
            $this->xfer_job_data( $job_id, 'url' );
            $this->xfer_job_data( $job_id, 'user' );
            $pid = trim( shell_exec( HESTIA_CMD . "v-invoke-plugin quickstart_blueprint_file " . $job_id . " > /dev/null 2>/dev/null & echo $!" ) );
            $this->set_job_data( $job_id, 'pid', $pid );
            $this->xfer_job_data( $job_id, 'pid' );
        }

        /**
         * Cleanup the given job data from the session and file system.
         * @param string $job_id The unique job id.
         */
        public function cleanup_job_data( $job_id ) {
            $command = "rm -rf /home/admin/tmp/devstia_" . $job_id . "*";
            shell_exec( $command );
        }

        /**
         * Copy the website with options asynchonously with the given job id
         * @param string $job_id The unique job id.
         */
        public function copy_now( $job_id ) {
            $_REQUEST['user'] = $_SESSION['user'];
            $this->set_job_data( $job_id, 'request', $_REQUEST );
            $this->xfer_job_data( $job_id, 'request' );
            $this->xfer_job_data( $job_id, 'manifest' );
            $pid = trim( shell_exec( HESTIA_CMD . "v-invoke-plugin quickstart_copy_now " . $job_id . " > /dev/null 2>/dev/null & echo $!" ) );
            $this->set_job_data( $job_id, 'pid', $pid );
            $this->xfer_job_data( $job_id, 'pid' );
        }

        /** 
         * Create a unique job id, all jobs are prefixed with devstia_job_id for future listing.
         * @return string The unique job id.
         */
        public function create_job() {
            global $hcpp;
            $job_id = $hcpp->nodeapp->random_chars( 16 );
            if ( !isset( $_SESSION['devstia_jobs']) ) $_SESSION['devstia_jobs'] = [];
            $_SESSION['devstia_jobs'][$job_id] = [];
            return $job_id; 
        }

        /**
         * Delete the given export file.
         * @param string $file The file to delete.
         */
        public function delete_export( $file ) {
            global $hcpp;
            $hcpp->run('invoke-plugin quickstart_delete_export ' . $file );
        }

        /**
         * Check if the given job id is valid.
         * @param string $job_id The unique job id.
         */
        public function is_job_valid( $job_id ) {
            if ( !isset( $_SESSION['devstia_jobs'][$job_id] ) ) return false;
            return true;
        }

        /**
         * Get the given job data with a reference key.
         * @param string $job_id The unique job id.
         * @param string $key The key of the data to get.
         * @return mixed The data value.
         */
        public function get_job_data( $job_id, $key ) {
            if ( !isset( $_SESSION['devstia_jobs'][$job_id] ) ) return false;
            if ( !isset( $_SESSION['devstia_jobs'][$job_id][$key] ) ) return false;
            return $_SESSION['devstia_jobs'][$job_id][$key];
        }

        /**
         * Remove the user's cookies associated with the remove session; logging them out.
         */
        public function priv_log_user_logout( $args ) {
            $user = $args[0];
            $command = "rm -f /home/admin/tmp/devstia_$user-cookies.dat";
            global $hcpp;
            $command = $hcpp->do_action( 'quickstart_priv_log_user_logout', $command );
            shell_exec( $command );
            return $args;
        }
        /**
         * Return the source from the given devstia.com URL with optional POST data.
         * @param string $url The URL to proxy.
         * @param string $postData The POST data to send.
         * @return array The response and headers.
         */
        public function proxy_devstia( $url, $postData = null ) {
        
            // Only allow devstia.com access
            if (substr($url, 0, 19) !== 'https://devstia.com') {
                return [
                    'response' => 'Access denied',
                    'headers' => [
                        'http_code' => 403
                    ]
                ];
            }
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
            // Support cookies
            $user = $_SESSION['user'];
            curl_setopt($ch, CURLOPT_COOKIEJAR, "/home/admin/tmp/devstia_$user-cookies.dat");
            curl_setopt($ch, CURLOPT_COOKIEFILE, "/home/admin/tmp/devstia_$user-cookies.dat");
        
            // Support POST data
            if ($postData !== null) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            }
        
            $response = curl_exec($ch);
            $responseHeaders = curl_getinfo($ch);
            curl_close($ch);
            return [
                'response' => $response,
                'headers' => $responseHeaders
            ];
        }

        /**
         * Remove the websites and associated resources based on the given job id.
         * @param string $job_id The unique job id.
         */
        public function remove_now( $job_id ) {
            $_REQUEST['user'] = $_SESSION['user'];
            $this->set_job_data( $job_id, 'request', $_REQUEST );
            $this->xfer_job_data( $job_id, 'request' );
            $pid = trim( shell_exec( HESTIA_CMD . "v-invoke-plugin quickstart_remove_now " . $job_id . " > /dev/null 2>/dev/null & echo $!" ) );
            $this->set_job_data( $job_id, 'pid', $pid );
            $this->xfer_job_data( $job_id, 'pid' );
        }

        /**
         * Set the given job data with a reference key.
         * @param string $job_id The unique job id.
         * @param string $key The key of the data to set.
         * @param mixed $value The value of the data to set.
         * @return bool True if successful, false otherwise.
         */
        public function set_job_data( $job_id, $key, $value ) {
            if ( !isset( $_SESSION['devstia_jobs'][$job_id] ) ) return false;
            $_SESSION['devstia_jobs'][$job_id][$key] = $value;
            return true;
        }

        /**
         * Export the website to a zip file with the given manifest.
         * @param array $manifest The manifest of the website to export.
         */
        public function export_zip( $manifest ) {

            // Transfer the manifest so we can pick it up in our privileged process
            $this->set_job_data( $manifest['job_id'], 'manifest', $manifest );
            $this->xfer_job_data( $manifest['job_id'], 'manifest' );

            // Start the export process asynchonously and get the process id
            $pid = trim( shell_exec( HESTIA_CMD . "v-invoke-plugin quickstart_export_zip " . $manifest['job_id'] . " > /dev/null 2>/dev/null & echo $!" ) );

            // Store the process id data for the job, to be used for status checks
            $this->set_job_data( $manifest['job_id'], 'pid', $pid );
            $this->xfer_job_data( $manifest['job_id'], 'pid' );
        }
        
        /**
         * Decompress the website asynchonously with the given job id
         * @param string $job_id The unique job id.
         */
        public function import_file( $job_id ) {
            $this->xfer_job_data( $job_id, 'import_file' );
            $pid = trim( shell_exec( HESTIA_CMD . "v-invoke-plugin quickstart_import_file " . $job_id . " > /dev/null 2>/dev/null & echo $!" ) );
            $this->set_job_data( $job_id, 'pid', $pid );
            $this->xfer_job_data( $job_id, 'pid' );
        }

        /**
         * Import the website with user options asynchonously with the given job id
         * @param string $job_id The unique job id.
         */
        public function import_now( $job_id ) {
            $_REQUEST['user'] = $_SESSION['user'];
            $this->set_job_data( $job_id, 'request', $_REQUEST );
            $this->xfer_job_data( $job_id, 'request' );
            $pid = trim( shell_exec( HESTIA_CMD . "v-invoke-plugin quickstart_import_now $job_id > /dev/null 2>/dev/null & echo $!" ) );
            $this->set_job_data( $job_id, 'pid', $pid );
            $this->xfer_job_data( $job_id, 'pid' );
        }

        /**
         * Get the site manifest for the given user's website domain. Highly optimized for speed,
         * scan revelent files for db credentials, and migration details (domain, aliases, 
         * user path) and return site and database details as an associative array.
         * 
         * @param string $user The username of the user.
         * @param string $domain The domain of the website.
         * @return array An associative array of the site details.
         */
        public function get_manifest( $user, $domain ) {
            global $hcpp;
            return $hcpp->run( "invoke-plugin quickstart_get_manifest " . $user . " " . $domain . " json" );
        }

        /**
         * Get an array of multiple manifests for the given stored with the job id.
         * @param string $job_id The unique job id.
         */
        public function get_multi_manifests( $job_id ) {
            global $hcpp;
            $user = $_SESSION['user'];
            $domains = $_GET['domain'];
            $this->set_job_data( $job_id, 'get_multi_manifests', [
                "user" => $user,
                "domains" => $domains
            ] );
            $this->xfer_job_data( $job_id, 'get_multi_manifests' );
            
            // Start the gathering process asynchonously and get the process id
            $pid = trim( shell_exec( HESTIA_CMD . "v-invoke-plugin quickstart_get_multi_manifests $job_id $user $domains > /dev/null 2>/dev/null & echo $!" ) );

            // Store the process id data for the job, to be used for status checks
            $this->set_job_data( $job_id, 'pid', $pid );
            $this->xfer_job_data( $job_id, 'pid' );
        }

        /**
         * Get a quickstart process status by job id.
         * @param string $job_id The job id for the process.
         * @return array An associative array of the process status.
         */
        public function get_status( $job_id ) {

            // Return last result status
            $result = $this->pickup_job_data( $job_id, 'result' );
            if ($result !== false) {
                return $result;
            }

            // Check for running process
            $pid = $this->peek_job_data( $job_id, 'pid' );
            if ( $pid === false ) {

                // Process ended, wait up to 15 seconds for final result to appear
                for ( $i = 0; $i < 15; $i++ ) {
                    $result = $this->pickup_job_data( $job_id, 'result' );
                    if ($result !== false) {
                        return $result;
                    }
                    sleep(1);
                }
                return [ 'status' => 'error', 'message' => "Error with Job ID \"$job_id.\"; ended with no final result." ];
            }else{
                
                // Check if pid exists
                $result = shell_exec( "ps -p $pid" );
                if ( strpos( $result, $pid ) === false ) {
                    $pid = $this->pickup_job_data( $job_id, 'pid' );
                    return [ 'status' => 'finished', 'message' => '' ];
                }else{
                    return [ 'status' => 'running', 'message' => '' ];
                }
            }
        }

        /**
         * Redirect to quickstart on login.
         * @param array $args The arguments passed to the command.
         */
        public function hcpp_head( $args ) {
            if ( !isset( $_GET['alt'] ) ) return $args;
            $content = $args['content'];
            if ( strpos( $content, 'LOGIN') === false ) return $args;
            $_SESSION['request_uri'] = '/list/web/?quickstart=main';
            return $args;
        }

        /**
         * Run trusted elevated commands from the v-invoke-plugin command.
         * @param array $args The arguments passed to the command.
         */
        public function hcpp_invoke_plugin( $args ) {
            $trusted = [
                'quickstart_cancel_job',
                'quickstart_copy_now',
                'quickstart_delete_export',
                'quickstart_blueprint_file',
                'quickstart_export_zip',
                'quickstart_get_manifest',
                'quickstart_get_multi_manifests',
                'quickstart_import_file',
                'quickstart_import_now',
                'quickstart_remove_now',
                'quickstart_connect_now',
                'quickstart_connect_save',
                'quickstart_start_upload_server',
                'quickstart_stop_upload_server'
            ];
            if ( in_array( $args[0], $trusted ) ) {
                return call_user_func_array([$this, $args[0]], [$args]);
            }else{
                return $args;
            }
        }

        /**
         * Clean up all the devstia_* files in /tmp and /home/user/tmp folders
         * on reboot.
         * @param array $args The arguments passed to the command.
         */
        public function hcpp_rebooted( $args ) {

            // Clean up /tmp/devstia_* files
            shell_exec('rm -rf /tmp/devstia_*');

            // Cycle through all users and remove /home/user/tmp/devstia_* folders
            global $hcpp;
            $users = $hcpp->run( "list-users json" );
            $cmd = '';
            foreach( $users as $user => $details) {
                $cmd .= "rm -rf /home/$user/tmp/devstia_* ; ";   
            }
            shell_exec( $cmd );
            return $args;
        }

        /**
         * Render the Quickstart pages in the body.
         * @param array $args The arguments passed to the command.
         */
        public function hcpp_render_body( $args ) {
            if ( !isset( $_GET['quickstart'] ) ) return $args;
            $authorized_pages = [
                'main',
                'import_options',
                'import_export',
                'import_now',
                'import',
                'export',
                'export_view',
                'export_details',
                'export_options',
                'export_now',
                'create_options',
                'create_now',
                'create',
                'remove_copy',
                'connect',
                'connect_now',
                'copy_details',
                'copy_now',
                'remove_details',
                'remove_now'
            ];

            // Sanitize the quickstart parameter, default to main
            $load = $_GET['quickstart'];
            $load = str_replace(array('/', '\\'), '', $load);
            if ( empty( $load ) || !preg_match( '/^[A-Za-z0-9_-]+$/', $load ) ) {
                $load = 'main';
            } 
            if ( !in_array( $load, $authorized_pages ) ) {
                $load = 'main';
            }

            // Check for valid user session or direct to login
            if(session_status() == PHP_SESSION_NONE){
                session_start();
            }
            if ( !isset( $_SESSION['user'] ) ) {
                header('Location: /login/?alt=1');
                exit;
            }

            // Load the requested page
            global $hcpp;
            ob_start();
            require( __DIR__ . '/pages/' . $load . '.php' );
            $page = ob_get_clean();
            $content = $args['content'];
            $footer = '<footer ' . $hcpp->delLeftMost( $content, '<footer ');
            $page = $hcpp->do_action('quickstart_' . $load, $page);
            $args['content'] = $page . $footer;    
            return $args;
        }

        /**
         * Render the quickstart panel tab.
         * @param array $args The arguments passed to the command.
         */
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
           
            // Customize help link
            $before = str_replace( 'href="https://hestiacp.com/docs/"', 'href="https://devstia.com/personal-web/support/"', $before );

            $content = $before . $qs_tab . $after;
            $args['content'] = $content;
            return $args;
        }

        /**
         * Check if the given data is serialized.
         * @param string $data The data to check.
         * @return bool True if serialized, false otherwise.
         */
        public function is_serialized( $data, $strict = true ) {
            if (strlen( $data) < 4 ) {
                return false;
            }
            if ( $data[1] !== ':' ) {
                return false;
            }
            if ( $data === 'N;' ) {
                return true;
            }
            if ( $strict) {
                $lastc = $data[strlen( $data) - 1];
                if ( $lastc !== ';' && $lastc !== '}' ) {
                    return false;
                }
            } else {
                $semicolon = strpos( $data, ';' );
                $brace = strpos( $data, '}' );
                // Either ; or } must exist.
                if ( $semicolon === false && $brace === false ) {
                    return false;
                }
                // But neither must be in the first X characters.
                if ( $semicolon !== false && $semicolon < 3 ) {
                    return false;
                }
                if ( $brace !== false && $brace < 4 ) {
                    return false;
                }
            }
            $token = $data[0];
            switch ( $token ) {
                case 's':
                    if ( $strict ) {
                        if ( $data[strlen( $data) - 2] !== '"' ) {
                            return false;
                        }
                    } else if (!strpos( $data, '"' ) ) {
                        return false;
                    }
                    // Or else fall through.
                case 'a':
                case 'O':
                case 'E':
                    return (bool)preg_match( "/^" . $token . ":[0-9]+:/", $data );
                case 'b':
                case 'i':
                case 'd':
                    $end = $strict ? '$' : '';
                    return (bool)preg_match( "/^" . $token . ":[0-9.E+-]+;" . $end . "/", $data );
            }
            return false;
        }

        /**
         * Pickup the given job data from a file and remove it; this allows a priviledged
         * process to get otherwise inaccessible admin session data.
         * @param string $job_id The unique job id.
         * @param string $key The key of the data to pickup.
         * @return mixed The data value.
         */
        public function pickup_job_data( $job_id, $key ) {
            $value = $this->peek_job_data( $job_id, $key );
            $file = "/home/admin/tmp/devstia_" . $job_id . "-" . $key . ".json";
            if ( file_exists( $file ) ) {
                unlink( $file );
            }
            return $value;
        }

        /**
         * Return the given job data from a file; this allows a priviledged
         * process to get otherwise inaccessible admin session data.
         * @param string $job_id The unique job id.
         * @param string $key The key of the data to pickup.
         * @return mixed The data value.
         */
        public function peek_job_data( $job_id, $key ) {
            $file = "/home/admin/tmp/devstia_" . $job_id . "-" . $key . ".json";
            if ( file_exists( $file ) ) {
                try {
                    $value = file_get_contents( $file );
                    $value = json_decode( $value, true );
                    return $value;
                } catch (Exception $e) {
                    return false;
                }
            }else{
                return false;
            }
        }

        /**
         * Our trusted elevated command to cancel a job; used by $this->cancel_job().
         * @param array $args The arguments passed to the command.
         */
        public function quickstart_cancel_job( $args ) {
            $job_id = $args[1];
            $pid = $args[2];
            $this->report_status( $job_id, 'Export cancelled.' );
            shell_exec( "kill -9 $pid" );
            $this->cleanup_job_data( $job_id );
            return $args;
        }

        /**
         * Save devstia.com credentials to the protected user's .devstia-com file.
         */
        public function quickstart_connect_save( $args ) {
            $job_id = $args[1];
            $user = $args[2];
            $file = "/tmp/devstia_$job_id-devstia-com";
            $data = file_get_contents( $file );
            unlink( $file );
            $file = "/home/$user/.devstia-com";
            file_put_contents( $file, $data );
            chmod( $file, 0600 );
            chown( $file, $user );
            chgrp( $file, $user );
            return $args;
        }

        /**
         * Return the devstia.com credentials from the protected user's .devstia-com file.
         */
        public function quickstart_connect_now( $args ) {
            $user = $args[1];
            global $hcpp;
            $hcpp->log( " THIS IS THE USER: $user" );
            $file = "/home/$user/.devstia-com";
            if ( file_exists( $file ) ) {
                $data = file_get_contents( $file );
                echo $data;
            }
            return $args;
        }

        /**
         * Our trusted elevated command to copy a website with user options asynchonously; used by $this->copy_now().
         */
        public function quickstart_copy_now( $args ) {
            global $hcpp;
            $job_id = $args[1];

            // Load manifest and request
            $manifest = $this->pickup_job_data( $job_id, 'manifest' );
            $manifest = $hcpp->do_action( 'quickstart_copy_now_manifest', $manifest ); // Allow plugins to modify
            $request = $this->pickup_job_data( $job_id, 'request' );
            $request = $hcpp->do_action( 'quickstart_copy_now_request', $request ); // Allow plugins to modify
            
            // Get original website details from manifest
            $orig_user = $manifest['user'];
            $orig_domain = $manifest['domain'];
            $orig_aliases = $manifest['aliases'];
            $proxy_ext = $manifest['proxy_ext'];
            $proxy = $manifest['proxy'];
            $backend = $manifest['backend'];

            // Gather new website details from request
            $new_user = $request['user'];
            $new_domain = strtolower( $request['v_domain'] );
            $new_aliases = strtolower( trim( str_replace( "\r\n", ",", $request['v_aliases'] ) ) );
            if ( count( explode( ',', $new_aliases ) ) != count( $orig_aliases ) ) {
                $this->report_status( $job_id, 'Number of aliases does not match original for substitution.', 'error' );
                return $args;
            }            

            // Create the new website domain with new aliases
            $this->report_status( $job_id, 'Please wait. Creating domain ' . $new_domain . '.' );
            $details = $hcpp->run('list-user-ips ' . $new_user . ' json');
            $first_ip = null;
            foreach ( $details as $ip => $ip_details ) {
                $first_ip = $ip;
                break;
            }
            $command = "add-web-domain $new_user $new_domain $first_ip no \"$new_aliases\" $proxy_ext";
            $result = $hcpp->run( $command );
            if ( $result != '' ) {
                $this->report_status( $job_id, $result, 'error' );
                return $args;
            }
            $new_aliases = explode( ',', $new_aliases );

            // Wait up to 60 seconds for public_html/index.html to be created
            $dest_folder = '/home/' . $new_user . '/web/' . $new_domain;
            for ( $i = 0; $i < 60; $i++ ) {
                if ( file_exists( $dest_folder . '/public_html/index.html' ) ) break;
                sleep(1);
            }
            if ( !is_dir( $dest_folder . '/public_html' ) ) {
                $this->report_status( $job_id, 'Error timeout awaiting domain creation. ' . $dest_folder, 'error' );
                return $args;
            }

            // Copy all subfolders in the import folder
            $import_folder = "/home/$orig_user/web/$orig_domain";
            $this->report_status( $job_id, 'Please wait. Copying files.' );
            $folders = array_filter( glob( $import_folder . '/*' ), 'is_dir' );

            // Apply write owner permissions on the domain folder and remove the index.html
            $command = "chmod 750 $dest_folder ; rm -f $dest_folder/public_html/index.html ; ";
            foreach( $folders as $folder ) {
                $subfolder = $hcpp->getRightMost( $folder, '/' );
                $command .= __DIR__ . '/abcopy ' . $folder . '/ ' . $dest_folder . "/$subfolder/ ; ";
                $command .= "chown -R $new_user:$new_user " . $dest_folder . "/$subfolder/ ; ";
                if ( $subfolder == 'public_html' ) {
                    $command .= "chown $new_user:www-data " . $dest_folder . "/$subfolder/ ; ";
                }
            }
            $command = $hcpp->do_action( 'quickstart_copy_copy_files', $command ); // Allow plugin mods
            shell_exec( $command );

            // Cull unselected databases
            $selected_databases = [];
            if ( isset( $request['selected_databases'] ) ) {
                $this->report_status( $job_id, 'Please wait. Copying databases.' );
                $selected_databases = $request['selected_databases'];
            }
            $manifest['databases'] = array_filter( $manifest['databases'], function( $db ) use ( $selected_databases, $dest_folder, $orig_user ) {
                $use_db = in_array( $db['DATABASE'], $selected_databases );

                // Dump the database if selected
                if ( $use_db ) {
                    $devstia_databases_folder = $dest_folder . '/devstia_databases';
                    if ( !is_dir( $devstia_databases_folder ) ) {
                        mkdir( $devstia_databases_folder );
                    }
                    $db_file = $devstia_databases_folder . '/' . $db['DATABASE'] . '.sql';
                    global $hcpp;
                    $hcpp->run( "dump-database $orig_user " . $db['DATABASE'] . " > \"$db_file\"" );
                }
                return $use_db;
            });

            // Create the databases
            $orig_dbs = $manifest['databases'];
            if ( is_array( $orig_dbs ) && !empty( $orig_dbs ) ) {
                foreach( $orig_dbs as $db ) {

                    // Get the original database details
                    $orig_db = $db['DATABASE'];
                    $orig_password = $db['DBPASSWORD'];
                    $orig_type = $db['TYPE'];
                    $orig_charset = $db['CHARSET'];
                    $ref_files = $db['ref_files'];

                    // Generate new credentials and new database
                    $db_name = strtolower( $hcpp->nodeapp->random_chars(5) );
                    $db_password = $hcpp->nodeapp->random_chars(20);
                    $command = "add-database $new_user $db_name $db_name $db_password $orig_type localhost $orig_charset";
                    $db_name = $new_user . '_' . $db_name;
                    $this->report_status( $job_id, "Please wait. Creating database: $db_name" );
                    $result = $hcpp->run( $command );

                    // Search and replace credentials in ref_files
                    foreach( $ref_files as $file ) {
                        $file = $dest_folder . '/' . $hcpp->delLeftMost( $file, '/' );
                        try {
                            $this->search_replace_file( 
                                $file, 
                                [$orig_db, $orig_password], 
                                [$db_name, $db_password] 
                            );
                        }catch( Exception $e ) {
                            $this->report_status( $job_id, $e->getMessage(), 'error' );
                            return $args;
                        }
                    }

                    // Search and replace domain, user path, and aliases in db sql files
                    $db_sql_file = $dest_folder . '/devstia_databases/' . $db['DATABASE'] . '.sql';
                    $searches = [$orig_domain, "/home/$orig_user"];
                    $replaces = [$new_domain, "/home/$new_user"];
                    $searches = array_merge( $searches, $orig_aliases );
                    $replaces = array_merge( $replaces, $new_aliases );
                    try {
                        $this->search_replace_file( $db_sql_file, $searches, $replaces );
                    }catch( Exception $e ) {
                        $this->report_status( $job_id, $e->getMessage(), 'error' );
                        return $args;
                    }

                    // Import the database sql file
                    if ( $orig_type == 'mysql' ) {

                        // Support MySQL
                        $command = "mysql -h localhost -u $db_name -p$db_password $db_name < $db_sql_file";
                    }else{

                        // Support PostgreSQL
                        $command = "export PGPASSWORD=\"$db_password\"; psql -h localhost -U $db_name $db_name -f $db_sql_file";
                    }
                    $command = $hcpp->do_action( 'quickstart_copy_now_db', $command ); // Allow plugin mods
                    $result = shell_exec( $command );
                    if ( strpos( strtolower( $result ), 'error' ) !== false ) {
                        $this->report_status( $job_id, $result, 'error' );
                        return $args;
                    }
                }
            }

            // Update smtp.json file
            $smtp_file = $dest_folder . '/private/smtp.json';
            if ( file_exists( $smtp_file ) ) {
                try {
                    // Get the original file's permissions and ownership
                    $fileStat = stat( $smtp_file );
                    $fileMode = $fileStat['mode'];
                    $fileUid = $fileStat['uid'];
                    $fileGid = $fileStat['gid'];

                    // Update the file
                    $content = file_get_contents( $smtp_file );
                    $content = json_decode( $content, true );
                    $content['username'] = $new_domain;
                    $content['password'] = $hcpp->nodeapp->random_chars( 16 );
                    file_put_contents( $smtp_file, json_encode( $content, JSON_PRETTY_PRINT ) );

                    // Restore the original file's permissions and ownership
                    chmod( $smtp_file, $fileMode );
                    chown( $smtp_file, $fileUid );
                    chgrp( $smtp_file, $fileGid );
                }catch( Exception $e ) {
                    $this->report_status( $job_id, $e->getMessage(), 'error' );
                    return $args;
                }
            }

            // Search and replace on base files
            foreach( $manifest['ref_files'] as $file ) {
                $file = $dest_folder . '/' . $hcpp->delLeftMost( $file, '/' );
                if ( !file_exists( $file ) ) continue;
                try {
                    $searches = [$orig_domain, "/home/$orig_user"];
                    $replaces = [$new_domain, "/home/$new_user"];
                    $searches = array_merge( $searches, $orig_aliases );
                    $replaces = array_merge( $replaces, $new_aliases );
                    $this->search_replace_file( 
                        $file, 
                        $searches,
                        $replaces
                    );
                }catch( Exception $e ) {
                    $this->report_status( $job_id, $e->getMessage(), 'error' );
                    return $args;
                }
            }

            // Search and replace export advanced options
            $this->report_status( $job_id, 'Please wait. Updating files.');
            $export_adv_options = $manifest['export_adv_options'];
            foreach( $export_adv_options as $option ) {

                // Get original value
                $value = $option['value'];
                $label = $option['label'];
                $ref_files = $option['ref_files'];
                if ( $label == '' ) continue;

                // Find new value from form
                $labelVar = 'eao_' . $this->title_to_var_name( $label );
                $new_value = '';
                if ( isset( $request[$labelVar] ) ) {
                    $new_value = $request[$labelVar];
                }

                // Get default value if multiselect
                if ( strpos( $value, "|") !== false ) {
                    $value = $hcpp->delLeftMost( $value, "|");
                    $value = $hcpp->getLeftMost( $value, "\n");
                }

                // Search and replace the value in ref. files
                foreach( $ref_files as $file ) {
                    $file = $dest_folder . '/' . $hcpp->delLeftMost( $file, '/' );
                    if ( !file_exists( $file ) ) continue;
                    if ( $value == $new_value ) continue;
                    try {
                        $this->search_replace_file( 
                            $file, 
                            [$value], 
                            [$new_value] 
                        );
                    }catch( Exception $e ) {
                        $this->report_status( $job_id, $e->getMessage(), 'error' );
                        return $args;
                    }
                }
            }

            // Search and replace export advanced options
            $export_adv_options = $manifest['export_adv_options'];
            foreach( $export_adv_options as $option ) {

                // Get original value
                $value = $option['value'];
                $label = $option['label'];
                $ref_files = $option['ref_files'];
                if ( $label == '' ) continue;

                // Find new value from form
                $labelVar = 'eao_' . $this->title_to_var_name( $label );
                $new_value = '';
                if ( isset( $request[$labelVar] ) ) {
                    $new_value = $request[$labelVar];
                }

                // Get default value if multiselect
                if ( strpos( $value, "|") !== false ) {
                    $value = $hcpp->delLeftMost( $value, "|");
                    $value = $hcpp->getLeftMost( $value, "\n");
                }

                // Search and replace the value in ref. files
                foreach( $ref_files as $file ) {
                    $file = $dest_folder . '/' . $hcpp->delLeftMost( $file, '/' );
                    if ( !file_exists( $file ) ) continue;
                    if ( $value == $new_value ) continue;
                    try {
                        $this->search_replace_file( 
                            $file, 
                            [$value], 
                            [$new_value] 
                        );
                    }catch( Exception $e ) {
                        $this->report_status( $job_id, $e->getMessage(), 'error' );
                        return $args;
                    }
                }
            }
            shell_exec( 'rm -rf ' . $dest_folder . '/devstia_databases' );

            // Update the web domain backend
            $hcpp->run( "change-web-domain-backend-tpl $new_user $new_domain $backend" );
            $hcpp->run( "change-web-domain-proxy-tpl $new_user $new_domain $proxy" );
            $hcpp->run( "restart-proxy" );
            $this->cleanup_job_data( $job_id );

            // Restart web and proxy
            $hcpp->run( "restart-web" );
            $hcpp->run( "restart-proxy" );

            // Report success
            $message = "Website copied successfully. You can now visit <br>your website at: ";
            $message .= "<a href=\"https://$new_domain\" target=\"_blank\"><i tabindex=\"100\" ";
            $message .= "style=\"font-size:smaller;\" class=\"fas fa-external-link\"></i> $new_domain</a>.";
            $this->report_status( $job_id, $message, 'finished' );
            return $args;
        }

        /**
         * Our trusted elevated command to delete an export archive; used by $this->delete_export().
         */
        public function quickstart_delete_export( $args ) {
            $file = $args[1];
            shell_exec( "rm -f $file" );
            return $args;
        }

        /**
         * Our trusted elevated command to download a blueprint file; used by $this->quickstart_blueprint_file().
         */
        public function quickstart_blueprint_file( $args ) {

            // Check if the blueprint file is already downloaded
            $job_id = $args[1];
            $user = $this->peek_job_data( $job_id, 'user' );
            $url = $this->peek_job_data( $job_id, 'url' );
            $blueprint_folder = basename( $url );
            if ( substr( $blueprint_folder, -4 ) == '.zip' ) {
                $blueprint_folder = substr( $blueprint_folder, 0, -4 );
            }
            $blueprint_folder = "/home/$user/web/blueprints/$blueprint_folder";

            if ( is_dir( $blueprint_folder ) ) {
                $this->report_status( $job_id, 'Blueprint file already downloaded.', 'finished' );
                return $args;
            }else{
                $this->report_status( $job_id, "Downloading blueprint file...<br>(0 bytes received)", 'running');
            }

            // Download the blueprint file using curl monitor/report the progress
            $file = "/tmp/devstia_" . $job_id . "-" . basename( $url );
            $command = "curl -H 'Cache-Control: no-cache' -o $file $url > /dev/null 2>/dev/null & echo $!";

            // Allow plugins to modify the command
            global $hcpp;
            $command = $hcpp->do_action( 'quickstart_blueprint_file_command', $command );
            $dl_pid = trim( shell_exec( $command ) );
            $status = null;

            // Check up every 3 seconds if the $dl_pid is still running or timeout after 15 minutes
            for ( $i = 0; $i < (60 * 15); $i++ ) {

                // Get and report the file size
                clearstatcache( true, $file );
                $file_size = filesize( $file );
                if ( $file_size === false ) {
                    $file_size = "0";
                }
                $this->report_status( $job_id, "Downloading blueprint file...<br>($file_size bytes received)", 'running');

                $result = shell_exec( "ps -p $dl_pid" );
                if ( strpos( $result, $dl_pid ) === false ) {
                    break;
                }

                // Check if there was an error
                pcntl_waitpid( $dl_pid, $status );
                if ( pcntl_wifexited( $status ) && pcntl_wexitstatus( $status ) != 0 ) {
                    $this->report_status( $job_id, 'Error downloading the blueprint file.', 'error' );

                    // Clean up the file
                    sleep(1);
                    if ( file_exists( $file ) ) {
                        unlink( $file );
                    }
                    return $args;
                }
                sleep(3);
            }

            // Report download success
            $this->report_status( $job_id, 'Download blueprint complete. Now decompressing files.' );

            // Decompress the file to user's download folder and update owner to allow user access
            $command = "";
            if ( ! is_dir( "/home/$user/web/blueprints") ) {
                $command .= "mkdir -p /home/$user/web/blueprints ; ";
                $command .= "chown $user:$user /home/$user/web/blueprints ; ";
            }
            $command .= "unzip -o -q $file -d $blueprint_folder ; ";
            $command .= "rm -f $file ; ";
            $command .= "chown -R $user:$user $blueprint_folder";

            // Allow plugins to modify the command
            $command = $hcpp->do_action( 'quickstart_blueprint_file_decompress', $command );
            $dl_pid = trim( shell_exec( $command ) );

            // Check if devstia_manifest.json is at the root of import_folder
            if (!file_exists($blueprint_folder . '/devstia_manifest.json')) {
                // Locate devstia_manifest.json within subfolders (but not in the private subfolder)
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($blueprint_folder, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $file) {
                    // Skip the private subfolder
                    if (strpos($file->getPathname(), $blueprint_folder . '/private') === 0) {
                        continue;
                    }
                    if ($file->getFilename() === 'devstia_manifest.json') {
                        $subfolder = $file->getPath();
                        
                        // Move all files and folders from subfolder to import_folder
                        $files = new FilesystemIterator($subfolder, FilesystemIterator::SKIP_DOTS);
                        foreach ($files as $item) {
                            rename($item->getPathname(), $blueprint_folder . '/' . $item->getFilename());
                        }

                        // Remove the now-empty subfolder
                        shell_exec( 'rm -rf ' . $subfolder );
                        break;
                    }
                }
            }

            // Clean up .DS_Store files and __MACOSX directory
            $this->cleanup_import_folder($blueprint_folder);

            // Report finished
            // $this->report_status( $job_id, 'Finsihed.', 'finished' );
            // sleep(3);
            // $this->pickup_job_data( $job_id, 'pid' );
            return $args;
        }

        /**
         * Our trusted elevated command to export a website to a zip file; used by $this->export_zip().
         */
        public function quickstart_export_zip( $args ) {
            $job_id = $args[1];
            $manifest = $this->pickup_job_data( $job_id, 'manifest' );
            if ( $manifest == false ) return $args;
            
            global $hcpp;
            $user = $manifest['user'];
            $domain = $manifest['domain'];
            $export_options = $manifest['export_options'];
            $setup_script = $manifest['setup_script'];
            $setup_script = str_replace( "\r\n", "\n", $setup_script );
            unset( $manifest['setup_script'] );
            $export_folder = '/home/' . $user . '/tmp/devstia_export_' . $job_id;
            if ( !is_dir( $export_folder ) ) mkdir( $export_folder, true );
            file_put_contents( $export_folder . '/devstia_manifest.json', json_encode( $manifest, JSON_PRETTY_PRINT) );
            if ( trim( $setup_script ) != '' ) {
                file_put_contents( $export_folder . '/devstia_setup.sh', $setup_script );
            }

            // Dump databases to user tmp folder
            $devstia_databases_folder = $export_folder . '/devstia_databases';
            mkdir( $devstia_databases_folder, true );
            chmod( $devstia_databases_folder, 0751);
            foreach( $manifest['databases'] as $database ) {
                 $db = $database['DATABASE'];
                 $hcpp->run( "dump-database $user $db > \"$devstia_databases_folder/$db.sql\"" );
            }
            $public_html = "/home/$user/web/$domain/public_html";
            $nodeapp = "/home/$user/web/$domain/nodeapp";
            $private = "/home/$user/web/$domain/private";
            $cgi_bin = "/home/$user/web/cgi-bin";
            $document_errors = "/home/$user/web/$domain/document_errors";

            // Copy website folders to user tmp folder, accounting for export options
            $abcopy = __DIR__ . '/abcopy';
            $exvc = ';';
            if ( strpos($export_options, 'exvc') !== false ) $exvc = ' true;';
            $command = '';
            if ( strpos($export_options, 'public_html') !== false && is_dir( $public_html) ) {
                $command .= "$abcopy $public_html $export_folder/public_html" . $exvc;
            }
            if ( strpos($export_options, 'nodeapp') !== false &&  is_dir( $nodeapp ) ) {
                $command .= "$abcopy $nodeapp $export_folder/nodeapp" . $exvc;
            } 
            if ( strpos($export_options, 'private') !== false && is_dir( $private ) ) {
                $command .= "$abcopy $private $export_folder/private" . $exvc;
            }
            if ( strpos($export_options, 'cgi_bin') !== false && is_dir( $cgi_bin ) ) {
                $command .= "$abcopy $cgi_bin $export_folder/cgi-bin" . $exvc;
            }
            if ( strpos($export_options, 'document_errors') !== false && is_dir( $document_errors ) ) {
                $command .= "$abcopy $document_errors $export_folder/document_errors" . $exvc;
            }

            // Reset ownership, zip up contents, move to exports, and clean up
            $zip_file = "/home/$user/web/exports/" . $manifest['zip_file'];
            $command .= "chown -R $user:$user $export_folder && cd $export_folder ";
            $command .= "&& zip -r $export_folder.zip . && cd .. && rm -rf $export_folder ";
            $command .= "&& mkdir -p /home/$user/web/exports ";
            $command .= "&& mv $export_folder.zip $zip_file ";
            $command .= "&& chown -R $user:$user /home/$user/web/exports ";
            $command = $hcpp->do_action( 'quickstart_export_zip', $command ); // Allow plugin mods
            shell_exec( $command );
            return $args;
        }

        /**
         * Our trusted elevated command to decompress a website archive asynchonously; used by $this->import_file().
         */
        public function quickstart_import_file( $args ) {
            global $hcpp;
            $job_id = $args[1];
            $import_file = $this->pickup_job_data( $job_id, 'import_file' );
            //$import_file = "/home/admin/tmp/devstia_" . $job_id . "-import_file.json";
            $import_folder = $hcpp->getLeftMost( $import_file, '.' );
            if ( file_exists( $import_file ) ) {
                $command = 'unzip -o -q ' . $import_file . ' -d ' . $import_folder . ' ';
                $command .= '&& rm -rf ' . $import_file . ' ';
                $command .= '&& chown -R admin:admin ' . $import_folder;
                $command = $hcpp->do_action( 'quickstart_import_file_command', $command );
                shell_exec( $command );

                // Check if devstia_manifest.json is at the root of import_folder
                if (!file_exists($import_folder . '/devstia_manifest.json')) {
                    // Locate devstia_manifest.json within subfolders
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($import_folder, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );

                    foreach ($iterator as $file) {
                        // Skip the private subfolder
                        if (strpos($file->getPathname(), $import_folder . '/private') === 0) {
                            continue;
                        }
                        if ($file->getFilename() === 'devstia_manifest.json') {
                            $subfolder = $file->getPath();
                            
                            // Move all files and folders from subfolder to import_folder
                            $files = new FilesystemIterator($subfolder, FilesystemIterator::SKIP_DOTS);
                            foreach ($files as $item) {
                                rename($item->getPathname(), $import_folder . '/' . $item->getFilename());
                            }

                            // Remove the now-empty subfolder
                            shell_exec( 'rm -rf ' . $subfolder );
                            break;
                        }
                    }
                }

                // Clean up .DS_Store files and __MACOSX directory
                $this->cleanup_import_folder($import_folder);
            }
            return $args;
        }

        /**
         * Clean up .DS_Store files and __MACOSX directory
         */
        private function cleanup_import_folder( $import_folder ) {
            // Remove .DS_Store files
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator( $import_folder, RecursiveDirectoryIterator::SKIP_DOTS ),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ( $file->getFilename() === '.DS_Store' ) {
                    unlink( $file->getPathname() );
                }
            }

            // Remove __MACOSX directory
            $macosx_dir = $import_folder . '/__MACOSX';
            if ( is_dir( $macosx_dir ) ) {
                shell_exec( 'rm -rf ' . $macosx_dir );
            }
        }

        /**
         * Our trusted elevated command to import a website with user options asynchonously; used by $this->import_now().
         */
        public function quickstart_import_now( $args ) {
            global $hcpp;
            $job_id = $args[1];
            $request = $this->pickup_job_data( $job_id, 'request' );

            // Load manifest and request
            $import_folder = "/home/admin/tmp/devstia_" . $job_id . "-import";

            // Check for downloaded blueprint folder
            if ( !file_exists( $import_folder ) ) {
                $user = $this->peek_job_data( $job_id, 'user' );
                $url = $this->peek_job_data( $job_id, 'url' );
                if ( $user != false && $url != false ) {
                    $url = basename( $url );
                    $url = $hcpp->delRightMost( $url, '.zip' );
                    $import_folder = "/home/$user/web/blueprints/$url";
                }else{
                    $this->report_status( $job_id, 'Error: No import folder; user and URL not found.', 'error' );
                    return $args;
                }
            }
            $manifest_file = "$import_folder/devstia_manifest.json";
            if ( ! file_exists( $manifest_file ) ) {
                $this->report_status( $job_id, 'Error: Manifest file not found.', 'error' );
                return $args;
            }
            try {
                $manifest = json_decode( file_get_contents( $manifest_file ), true );
                $manifest = $hcpp->do_action( 'quickstart_import_now_manifest', $manifest ); // Allow plugins to modify
            }catch( Exception $e ) {
                $this->report_status( $job_id, 'Error: Manifest file could not be parsed.', 'error' );
                return $args;
            }            
            $request = $hcpp->do_action( 'quickstart_import_now_request', $request ); // Allow plugins to modify
            
            // Get original website details from manifest
            $orig_user = $manifest['user'];
            $orig_domain = $manifest['domain'];
            $orig_aliases = $manifest['aliases'];
            $proxy_ext = $manifest['proxy_ext'];
            $proxy = $manifest['proxy'];
            $backend = $manifest['backend'];

            // Gather new website details from request
            $new_user = $request['user'];
            $new_domain = strtolower( $request['v_domain'] );
            $new_aliases = strtolower( trim( str_replace( "\r\n", ",", $request['v_aliases'] ) ) );
            if ( count( explode( ',', $new_aliases ) ) != count( $orig_aliases ) ) {
                $this->report_status( $job_id, 'Number of aliases does not match original for substitution.', 'error' );
                return $args;
            }            

            // Create the new website domain with new aliases
            $this->report_status( $job_id, 'Please wait. Creating domain ' . $new_domain . '.' );
            $details = $hcpp->run('list-user-ips ' . $new_user . ' json');
            $first_ip = null;
            foreach ( $details as $ip => $ip_details ) {
                $first_ip = $ip;
                break;
            }
            $command = "add-web-domain $new_user $new_domain $first_ip no \"$new_aliases\" $proxy_ext";
            $result = $hcpp->run( $command );
            if ( $result != '' ) {
                $this->report_status( $job_id, $result, 'error' );
                return $args;
            }
            $new_aliases = explode( ',', $new_aliases );

            // Wait up to 60 seconds for public_html/index.html to be created
            $dest_folder = '/home/' . $new_user . '/web/' . $new_domain;
            for ( $i = 0; $i < 60; $i++ ) {
                if ( file_exists( $dest_folder . '/public_html/index.html' ) ) break;
                sleep(1);
            }
            if ( !is_dir( $dest_folder . '/public_html' ) ) {
                $this->report_status( $job_id, 'Error timeout awaiting domain creation. ' . $dest_folder, 'error' );
                return $args;
            }

            // Copy all subfolders in the import folder
            $this->report_status( $job_id, 'Please wait. Copying files.' );
            $folders = array_filter( glob( $import_folder . '/*' ), 'is_dir' );

            // Apply write owner permissions on the domain folder and remove the index.html
            $command = "chmod 750 $dest_folder ; rm -f $dest_folder/public_html/index.html ; ";
            foreach( $folders as $folder ) {
                $subfolder = $hcpp->getRightMost( $folder, '/' );
                $command .= __DIR__ . '/abcopy ' . $folder . '/ ' . $dest_folder . "/$subfolder/ ; ";
                $command .= "chown -R $new_user:$new_user " . $dest_folder . "/$subfolder/ ; ";
                if ( $subfolder == 'public_html' ) {
                    $command .= "chown $new_user:www-data " . $dest_folder . "/$subfolder/ ; ";
                }
            }

            // Copy over the devstia_setup.sh if present
            $setup_file = $import_folder . '/devstia_setup.sh';
            if ( file_exists( $setup_file ) ) {
                $command .= "cp $setup_file $dest_folder/devstia_setup.sh ; ";
                $command .= "chown $new_user:$new_user $dest_folder/devstia_setup.sh ; ";
            }

            $command = $hcpp->do_action( 'quickstart_import_copy_files', $command ); // Allow plugin mods
            shell_exec( $command );

            // Update smtp.json file
            $smtp_file = $dest_folder . '/private/smtp.json';
            if ( file_exists( $smtp_file ) ) {
                try {
                    // Get the original file's permissions and ownership
                    $fileStat = stat( $smtp_file );
                    $fileMode = $fileStat['mode'];
                    $fileUid = $fileStat['uid'];
                    $fileGid = $fileStat['gid'];

                    // Update the file
                    $content = file_get_contents( $smtp_file );
                    $content = json_decode( $content, true );
                    $content['username'] = $new_domain;
                    $content['password'] = $hcpp->nodeapp->random_chars( 16 );
                    file_put_contents( $smtp_file, json_encode( $content, JSON_PRETTY_PRINT ) );

                    // Restore the original file's permissions and ownership
                    chmod( $smtp_file, $fileMode );
                    chown( $smtp_file, $fileUid );
                    chgrp( $smtp_file, $fileGid );
                }catch( Exception $e ) {
                    $this->report_status( $job_id, $e->getMessage(), 'error' );
                    return $args;
                }
            }

            // Search and replace on base files
            foreach( $manifest['ref_files'] as $file ) {
                $file = $dest_folder . '/' . $hcpp->delLeftMost( $file, '/' );
                if ( !file_exists( $file ) ) continue;
                try {
                    $searches = [$orig_domain, "/home/$orig_user"];
                    $replaces = [$new_domain, "/home/$new_user"];
                    $searches = array_merge( $searches, $orig_aliases );
                    $replaces = array_merge( $replaces, $new_aliases );
                    $this->search_replace_file( 
                        $file, 
                        $searches,
                        $replaces
                    );
                }catch( Exception $e ) {
                    $this->report_status( $job_id, $e->getMessage(), 'error' );
                    return $args;
                }
            }

            // Search and replace export advanced options
            $this->report_status( $job_id, 'Please wait. Updating files.');
            $export_adv_options = $manifest['export_adv_options'];
            foreach( $export_adv_options as $option ) {

                // Get original value
                $value = $option['value'];
                $label = $option['label'];
                $ref_files = $option['ref_files'];
                if ( $label == '' ) continue;

                // Find new value from form
                $labelVar = 'eao_' . $this->title_to_var_name( $label );
                $new_value = '';
                if ( isset( $request[$labelVar] ) ) {
                    $new_value = $request[$labelVar];
                }

                // Get default value if multiselect
                if ( strpos( $value, "|") !== false ) {
                    $value = $hcpp->delLeftMost( $value, "|");
                    $value = $hcpp->getLeftMost( $value, "\n");
                }

                // Search and replace the value in ref. files
                foreach( $ref_files as $file ) {
                    $file = $dest_folder . '/' . $hcpp->delLeftMost( $file, '/' );
                    if ( !file_exists( $file ) ) continue;
                    if ( $value == $new_value ) continue;
                    try {
                        $this->search_replace_file( 
                            $file, 
                            [$value], 
                            [$new_value] 
                        );
                    }catch( Exception $e ) {
                        $this->report_status( $job_id, $e->getMessage(), 'error' );
                        return $args;
                    }
                }
            }

            // Search and replace export advanced options
            $export_adv_options = $manifest['export_adv_options'];
            foreach( $export_adv_options as $option ) {

                // Get original value
                $value = $option['value'];
                $label = $option['label'];
                $ref_files = $option['ref_files'];
                if ( $label == '' ) continue;

                // Find new value from form
                $labelVar = 'eao_' . $this->title_to_var_name( $label );
                $new_value = '';
                if ( isset( $request[$labelVar] ) ) {
                    $new_value = $request[$labelVar];
                }

                // Get default value if multiselect
                if ( strpos( $value, "|") !== false ) {
                    $value = $hcpp->delLeftMost( $value, "|");
                    $value = $hcpp->getLeftMost( $value, "\n");
                }

                // Search and replace the value in ref. files
                foreach( $ref_files as $file ) {
                    $file = $dest_folder . '/' . $hcpp->delLeftMost( $file, '/' );
                    if ( !file_exists( $file ) ) continue;
                    if ( $value == $new_value ) continue;
                    try {
                        $this->search_replace_file( 
                            $file, 
                            [$value], 
                            [$new_value] 
                        );
                    }catch( Exception $e ) {
                        $this->report_status( $job_id, $e->getMessage(), 'error' );
                        return $args;
                    }
                }
            }

            // Create the databases
            $orig_dbs = $manifest['databases'];
            if ( is_array( $orig_dbs ) && !empty( $orig_dbs ) ) {
                foreach( $orig_dbs as $db ) {

                    // Get the original database details
                    $orig_db = $db['DATABASE'];
                    $orig_password = $db['DBPASSWORD'];
                    $orig_type = $db['TYPE'];
                    $orig_charset = $db['CHARSET'];
                    $ref_files = $db['ref_files'];

                    // Generate new credentials and new database
                    $db_name = strtolower( $hcpp->nodeapp->random_chars(5) );
                    $db_password = $hcpp->nodeapp->random_chars(20);
                    $command = "add-database $new_user $db_name $db_name $db_password $orig_type localhost $orig_charset";
                    $db_name = $new_user . '_' . $db_name;
                    $this->report_status( $job_id, "Please wait. Creating database: $db_name" );
                    $result = $hcpp->run( $command );

                    // Search and replace credentials in ref_files
                    foreach( $ref_files as $file ) {
                        $file = $dest_folder . '/' . $hcpp->delLeftMost( $file, '/' );
                        try {
                            $this->search_replace_file( 
                                $file, 
                                [$orig_db, $orig_password], 
                                [$db_name, $db_password] 
                            );
                        }catch( Exception $e ) {
                            $this->report_status( $job_id, $e->getMessage(), 'error' );
                            return $args;
                        }
                    }

                    // Search and replace domain, user path, and aliases in db sql files
                    $db_sql_file = $dest_folder . '/devstia_databases/' . $db['DATABASE'] . '.sql';
                    $searches = [$orig_domain, "/home/$orig_user"];
                    $replaces = [$new_domain, "/home/$new_user"];
                    $searches = array_merge( $searches, $orig_aliases );
                    $replaces = array_merge( $replaces, $new_aliases );
                    try {
                        $this->search_replace_file( $db_sql_file, $searches, $replaces );
                    }catch( Exception $e ) {
                        $this->report_status( $job_id, $e->getMessage(), 'error' );
                        return $args;
                    }

                    // Import the database sql file
                    if ( $orig_type == 'mysql' ) {

                        // Support MySQL
                        $command = "mysql -h localhost -u $db_name -p$db_password $db_name < $db_sql_file";
                    }else{

                        // Support PostgreSQL
                        $command = "export PGPASSWORD=\"$db_password\"; psql -h localhost -U $db_name $db_name -f $db_sql_file";
                    }
                    $command = $hcpp->do_action( 'quickstart_import_now_db', $command ); // Allow plugin mods
                    $result = shell_exec( $command );
                    if ( strpos( strtolower( $result ), 'error' ) !== false ) {
                        $this->report_status( $job_id, $result, 'error' );
                        return $args;
                    }
                }
            }
            shell_exec( 'rm -rf ' . $dest_folder . '/devstia_databases' );

            // Run post-process devstia_setup.sh script if present and delete it
            $this->report_status( $job_id, "Please wait. Finishing setup." );
            $setup_script = $dest_folder . '/devstia_setup.sh';
            if ( file_exists( $setup_script ) ) {
                $hcpp->runuser( $new_user, "cd $dest_folder && chmod +x devstia_setup.sh && ./devstia_setup.sh" );
                shell_exec( 'rm -f ' . $setup_script );
            }

            // Update the web domain backend and proxy
            $hcpp->run( "change-web-domain-backend-tpl $new_user $new_domain $backend" );
            $hcpp->run( "change-web-domain-proxy-tpl $new_user $new_domain $proxy" );

            // Restart web and proxy
            $hcpp->run( "restart-web" );
            $hcpp->run( "restart-proxy" );

            // Report success
            $message = "Website imported successfully. You can now visit <br>your website at: ";
            if (strpos($import_folder, '/tmp') !== 0) {
                $message = "Website created successfully. You can now visit <br>your website at: ";
            }
            $message .= "<a href=\"https://$new_domain\" target=\"_blank\"><i tabindex=\"100\" ";
            $message .= "style=\"font-size:smaller;\" class=\"fas fa-external-link\"></i> $new_domain</a>.";
            $this->cleanup_job_data( $job_id );
            $this->report_status( $job_id, $message, 'finished' );
            return $args;
        }

        /**
         * Our trusted elevated command to get site manifest; used by $this->get_manifest().
         * @param array $args The arguments passed to the command.
         */
        public function quickstart_get_manifest( $args ) {
            $user = $args[1];
            $domain = $args[2];

            // Get a list of databases for the given user
            global $hcpp;
            $data = $hcpp->run( "list-databases " . $user . " json" );
            $databases = [];
            foreach ($data as $key => $value) {
                array_push( $databases, $value );
            }

            // Get a list of folders to scan for credentials
            $web_domain = $hcpp->run( "list-web-domain " . $user . " '" . $domain . "' json " );
            if ( $web_domain == NULL ) {
                echo json_encode( [ 'error' => 'Domain not found' ] );
                exit();
            }
            $public_html = $web_domain[$domain]['DOCUMENT_ROOT'];
            $nodeapp = str_replace( '/public_html/', '/nodeapp/', $public_html );

            // Gather web domain items for migration; mentions of domain, aliases, or user folder
            $aliases = $web_domain[$domain]['ALIAS'];
            $backend = $web_domain[$domain]['BACKEND'];
            $proxy = $web_domain[$domain]['PROXY'];
            $proxy_ext = $web_domain[$domain]['PROXY_EXT'];
            $template = $web_domain[$domain]['TPL'];
            $aliases = explode( ',', $aliases );
            $migrate_strings = [
                "/home/$user", 
                $domain
            ];
            $migrate_ref_files = [];
            if ( !empty( $aliases ) && $aliases[0] != '' ) {
                foreach( $aliases as $alias ) {
                    $migrate_strings[] = $alias;
                }
            }

            // Omit folders, and file extensions for scan
            $omit_folders = array( 'src', 'core', 'includes', 'public', 'current', 'content', 'core', 'uploads', 
                'logs', '.git', '.svn', '.hg', 'versions', 'node_modules', 'wp-content', 'wp-includes',
                'wp-admin', 'vendor', 'mw-config', 'extensions', 'maintenance', 'i18n', 'skins' );
            $match_extensions = array( 'php', 'ts', 'js', 'json', 'conf', 'config', 'jsx', 'ini', 'sh', 'xml', 'inc',
                'cfg', 'yml', 'yaml', 'py', 'rb', 'env' );

            // Get list of files to check from public_html folder
            $files = [];
            if ( is_dir( $public_html ) ) {

                // First get a list of all subfolders of public_html
                $omit_folders = array_map(function($folder) {
                    return '/' . $folder . '/';
                }, $omit_folders);
                $omit_folders_pattern = implode('|', array_map('preg_quote', $omit_folders));
                $command = "find $public_html -type d | egrep -v '$omit_folders_pattern'";
                $subfolders = array_filter(explode("\n", shell_exec($command)));

                // Given a list of subfolders, get a list of all files that have the match extensions
                $match_extensions_pattern = implode('|', array_map('preg_quote', $match_extensions));
                foreach( $subfolders as $folder) {
                    $command = "find $folder -maxdepth 1 -type f | egrep '$match_extensions_pattern'";    
                    $f = array_filter(explode("\n", shell_exec($command)));
                    $files = array_merge( $files, $f );
                }

            }

            // Get list of files to check from nodeapp folder
            if ( is_dir( $nodeapp ) ) {

                // First get a list of all subfolders of nodeapp
                $omit_folders_pattern = implode('|', array_map('preg_quote', $omit_folders));
                $command = "find $nodeapp -type d | egrep -v '$omit_folders_pattern'";
                $subfolders = array_filter(explode("\n", shell_exec($command)));

                // Given a list of subfolders, get a list of all files that have the match extensions
                $match_extensions_pattern = implode('|', array_map('preg_quote', $match_extensions));
                foreach( $subfolders as $folder) {
                    $command = "find $folder -maxdepth 1 -type f | egrep '$match_extensions_pattern'";    
                    $f = array_filter(explode("\n", shell_exec($command)));
                    $files = array_merge( $files, $f );
                }
            }
            
            // Check the given files 
            foreach( $files as $file ) {
                if ( strpos( $file, 'devstia_manifest.json' ) !== false ) continue;
                $content = file_get_contents( $file );
                $index = 0;

                // Check for mentions of database, domain, aliases
                foreach( $databases as $database ) {
                    if ( !isset( $database['ref_files'] ) ) $database['ref_files'] = [];
                    if ( strpos( $content, $database['DATABASE'] ) !== false ) {
                        $database['ref_files'][] = $file;
                    }
                    $databases[$index] = $database;
                    $index++;
                }

                // Check for any of the migrate strings
                foreach( $migrate_strings as $m ) {
                    if ( !in_array( $file, $migrate_ref_files ) ) {
                        if ( strpos( $content, $m ) !== false ) {
                            $migrate_ref_files[] = $file;
                        }else{
                            // Check escape encoded version too
                            if ( strpos( $content, addcslashes( $m, "\/\n\r\0" ) ) !== false ) {
                                $migrate_ref_files[] = $file;
                            }
                        }   
                    }
                }
            }

            // Analyze dbs that have assoc. files, and extract the password for each database
            $db_details = [];
            foreach( $databases as $database ) {
                if ( !isset( $database['ref_files'] ) || count( $database['ref_files'] ) == 0 ) continue;

                // Search for the password in the first reference file
                $database['DBPASSWORD'] = "";
                foreach( $database['ref_files'] as $file) {
                    $content = file_get_contents( $file );
                    $content = $hcpp->delLeftMost( $content, $database['DATABASE'] );
                    $pwkeys = ["PASSWORD'", 'PASSWORD"', 'password"', "password'", 'password =', 'password='];
                    $fkey = "";
                    foreach( $pwkeys as $pwkey ) {
                        $keypos = strpos( $content, $pwkey );
                        if ( $keypos === false ) continue;
                        if ( $fkey == "" ) $fkey = $pwkey;
                        if ( $keypos < strpos( $content, $fkey ) ) $fkey = $pwkey;
                    }
                    if ( $fkey == "" ) continue;
                }
                if ( $fkey != "" ) {
                    $content = $hcpp->delLeftMost( $content, $fkey );
                    
                    // Find the first instance of a single quote or double quote, whichever comes first
                    $singleQuotePos = strpos($content, "'");
                    $doubleQuotePos = strpos($content, '"');
                    if ($singleQuotePos === false && $doubleQuotePos === false) {
                        // No quotes found
                        $qchar = '';
                    } elseif ($singleQuotePos === false) {
                        // Only double quote found
                        $qchar = '"';
                    } elseif ($doubleQuotePos === false) {
                        // Only single quote found
                        $qchar = "'";
                    } else {
                        // Both quotes found, get the first one
                        $qchar = $singleQuotePos < $doubleQuotePos ? "'" : '"';
                    }
                    if ( $qchar == '' ) continue;

                    // Parse out the password enclosed in the quotes $qchar
                    $content = $hcpp->delLeftMost( $content, $qchar );
                    $password = $hcpp->getLeftMost( $content, $qchar );
                    $database['DBPASSWORD'] = $password;
                    $db_details[] = $database;
                    continue;
                }
            }

            // Make ref_files relative paths
            foreach( $db_details as $key => $db ) {
                foreach( $db['ref_files'] as $key2 => $file ) {
                    $db_details[$key]['ref_files'][$key2] = "." . $hcpp->delLeftMost( $file, $domain );
                }
            }
            foreach( $migrate_ref_files as $key => $file ) {
                $migrate_ref_files[$key] = "." . $hcpp->delLeftMost( $file, $domain );
            }

            // Output the site and database details
            $site_details = [ 
                'domain' => $domain,
                'aliases' => $aliases,
                'backend' => $backend,
                'proxy' => $proxy,
                'proxy_ext' => $proxy_ext,
                'template' => $template,
                'ref_files' => $migrate_ref_files,
                'databases' => $db_details,
                'user' => $user
            ];
            echo json_encode( $site_details );
            return $args;
        }

        /**
         * Our trusted elevated command to get multiple manifests; used by $this->get_multi_manifests().
         * @param array $args The arguments passed to the command.
         * 
         */
        public function quickstart_get_multi_manifests( $args ) {
            $job_id = $args[1];

            // Get the user and domains
            $gmm = $this->pickup_job_data( $job_id, 'get_multi_manifests' );
            $user = $gmm['user'];
            $domains = explode( ',', $gmm['domains'] );
            $manifests = [];
            foreach( $domains as $domain ) {
                $this->report_status( $job_id, "Obtaining details for " . $domain . "." );
                $args = [ $user, $domain ];
                $manifest = $this->get_manifest( $user, $domain );
                $manifests[] = $manifest;
            }
            $this->report_status( $job_id, $manifests, 'finished' );
            return $args;
        }

        /**
         * Remove the websites and databases from the server by job_id; used by $this->remove_websites().
         */
        public function quickstart_remove_now( $args ) {
            $job_id = $args[1];
            $request = $this->pickup_job_data( $job_id, 'request' );
            if ( $request == false ) return $args;
            if ( !isset( $request['manifests'] ) ) return $args;
            $manifests = json_decode( $request['manifests'], true );
            
            // Run removal commands for each manifest entry
            global $hcpp;
            foreach( $manifests as $manifest ) {
                $user = $manifest['user'];
                $domain = $manifest['domain'];
                $this->report_status( $job_id, "Removing " . $domain . "." );
                $command = "delete-web-domain $user $domain no";
                $result = $hcpp->run( $command );
                if ( $result != '' ) {
                    $this->report_status( $job_id, $result, 'error' );
                    return $args;
                }

                // Remove the databases
                $databases = $manifest['databases'];
                foreach( $databases as $db ) {
                    $db_name = $db['DATABASE'];
                    $command = "delete-database $user $db_name";
                    $result = $hcpp->run( $command );
                    if ( $result != '' ) {
                        $this->report_status( $job_id, $result, 'error' );
                        return $args;
                    }
                }
            }

            // Restart web and proxy
            $hcpp->run( "restart-web" );
            $hcpp->run( "restart-proxy" );

            // Report success
            $this->report_status( $job_id, "Website(s) removed successfully.", 'finished' );
            return $args;
        }

        /**
         * Report a quickstart process status by unique key.
         * @param string $key The unique key for the process.
         * @param string $message The message to report.
         * @param string $status The status to report.
         */
        public function report_status( $job_id, $message, $status = 'running' ) {
            $result_file = '/home/admin/tmp/devstia_' . $job_id . '-result.json';
            $result = json_encode( [ 'status' => $status, 'message' => $message ] );
            try {
                if ( file_exists( $result_file ) ) unlink( $result_file );
                file_put_contents( $result_file, $result );
                chown( $result_file, 'admin' );
                chgrp( $result_file, 'admin' );
            }catch( Exception $e ) {
                global $hcpp;
                $hcpp->log( $e->getMessage() );
            }
        }

        /**
         * Parses the given $sql fragment into an array of values based on the SQL syntax
         * containing single quote strings.
         * @param string $sql The SQL fragment to parse.
         * @return string[] The array of strings parsed from the SQL fragment.
         */
        public function parse_sql_sequence( $sql ) {
            $length = strlen($sql);
            $result = [];
            $i = 0;
        
            while ($i < $length) {
                if (ctype_digit($sql[$i])) {
                    // Parse integer
                    $start = $i;
                    while ($i < $length && ctype_digit($sql[$i])) {
                        $i++;
                    }
                    $result[] = substr($sql, $start, $i - $start);
                } elseif ($sql[$i] === "'") {
                    // Parse string
                    $start = $i;
                    $i++;
                    while ($i < $length) {
                        if ($sql[$i] === "\\" && $i + 1 < $length && $sql[$i + 1] === "'") {
                            // Skip escaped single quote
                            $i += 2;
                        } elseif ($sql[$i] === "'") {
                            // End of string
                            $i++;
                            break;
                        } else {
                            $i++;
                        }
                    }
                    $result[] = substr($sql, $start, $i - $start);
                } elseif ($sql[$i] === 'O' && $i + 1 < $length && $sql[$i + 1] === ':') {
                    // Parse serialized object
                    $start = $i;
                    $i += 2;
                    while ($i < $length && $sql[$i] !== '}') {
                        $i++;
                    }
                    if ($i < $length && $sql[$i] === '}') {
                        $i++;
                    }
                    $result[] = substr($sql, $start, $i - $start);
                } else {
                    $i++;
                }
            }
        
            return $result;
        }

        /**
         * Search and replace the given string in the given source text file, with special support
         * for PHP serialized strings in MySQL's "quickdump" file format, and the search for escaped
         * characters in the strings.
         * 
         * @param string $file The path and filename to the file to modify.
         * @param string|string[] $search The string or array of strings to search for.
         * @param string|string[] $replace The string or array of strings to replace with.
         */
        public function search_replace_file( $file, $search, $replace ) {
            global $hcpp;
            $hcpp->log( "Searching and replacing in file: $file" );
            $hcpp->log( "Search: " . json_encode( $search ) );
            $hcpp->log( "Replace: " . json_encode( $replace ) );

            // Check parameters
            if ( !file_exists( $file ) ) {
                throw new Exception( "File '$file' does not exist." );
            }
            if ( !is_string( $search ) && !is_array( $search ) ) {
                throw new Exception( "Parameter 'search' must be a string or array." );
            }
            if ( !is_string( $replace ) && !is_array( $replace ) ) {
                throw new Exception( "Parameter 'replace' must be a string or array." );
            }
            if ( is_string( $search ) ) {
                $search = [ $search ];
            }
            if ( is_string( $replace ) ) {
                $replace = [ $replace ];
            }
            if ( count( $search ) != count( $replace ) ) {
                throw new Exception( "Parameters 'search' and 'replace' must have the same number of elements." );
            }

            // Duplicate search and replace strings with escaped versions if necessary
            $searchEscaped = [];
            $replaceEscaped = [];
            for ($i = 0; $i < count($search); $i++) {
                $searchE = addcslashes($search[$i], "\/\n\r\0");
                
                // Check if already in array
                if ( in_array( $searchE, $search ) ) continue;
                $searchEscaped[] = $searchE;
                $replaceEscaped[] = addcslashes($replace[$i], "\/\n\r\0");
            }
            $search = array_merge( $search, $searchEscaped );
            $replace = array_merge( $replace, $replaceEscaped );

            // Load the file and replace the strings
            $handle = fopen( $file, 'r' );
            $tempFile = $file . '.tmp';
            $writeStream = fopen( $tempFile, 'w' );
            $regex1 = "/('.*?'|[^',\s]+)(?=\s*,|\s*;|\s*$)/";
            global $regex2;
            $regex2 = "/s:(\d+):\"(.*?)\";/ms";
            while ( ( $line = fgets( $handle ) ) !== false ) {
                $origLine = $line;
                $line = trim( $line );
                $bModifed = false;
                for ( $i = 0; $i < count( $search ); $i++ ) {
                    $searchString = $search[$i];
                    $replaceString = $replace[$i];
                    if ( strpos( $line, $searchString ) !== false && $searchString != $replaceString ) {

                        // Sense Quickdump format
                        if (strpos($line, "(") === 0 && (substr($line, -2) === ")," || substr($line, -2) === ");")) {
                            $startLine = substr( $line, 0, 1 );
                            $endLine = substr( $line, -2 );
                            $line = substr( $line, 1, -2 );
                            $line = str_replace("\\0", "~0Placeholder", $line );
                            // $matches = [];
                            // preg_match_all( $regex1, $line, $matches );
                            // $items = $matches[0];
                            $items = $this->parse_sql_sequence( $line );
                            $line = implode( '', [$startLine, implode( ",", array_map( function ( $item ) use ( $searchString, $replaceString ) {
                                if (strpos( $item, "'" ) === 0 && strrpos( $item, "'" ) === strlen( $item ) - 1 ) {
                                    $item = substr( $item, 1, -1 );
                                    $item = str_replace( $searchString, $replaceString, $item );

                                    // Sense serialized strings
                                    if ( $this->is_serialized( $item ) ) {

                                        // Recalculate the length of the serialized strings
                                        $item = json_decode(json_encode( $item ) );
                                        $item = str_replace( "\\", "", $item );
                                        $item = str_replace( "~0Placeholder", "\0", $item );
                                        global $regex2;
                                        $item = preg_replace_callback( $regex2, function ( $matches ) {
                                            return 's:' . strlen( $matches[2] ) . ':"' . $matches[2] . '";';
                                        }, $item);
                                        $item = addslashes( $item );
                                    }else{
                                        $item = str_replace( "\0", "~0Placeholder", $item );
                                    }
                                    return implode( '', ["'" , $item , "'"] );
                                } else if ( $item === 'null' ) {
                                    return null;
                                } else if ( is_numeric( $item ) ) {
                                    return (float)$item;
                                } else {
                                    return $item;
                                }
                            }, $items ) ), $endLine] );
                        }else{
                            $line = str_replace( $searchString, $replaceString, $line );
                        }
                        $bModifed = true;
                    }
                }
                if (false === $bModifed) {
                    $line = $origLine;
                }

                // Ensure the line ends with a newline
                if ( substr( $line, -1 ) != "\n" ) {
                    $line .= "\n";
                }
                fwrite( $writeStream, $line );
            }
            fclose( $handle );
            fclose( $writeStream );

            // Check for multi-line block in search and replace them in
            // the temp file as a whole because line-by-line won't work.
            for ( $i = 0; $i < count( $search ); $i++ ) {
                $searchString = $search[$i];
                $replaceString = $replace[$i];
                if ( strpos( $searchString, "\n" ) !== false ) {
                    $content = file_get_contents( $tempFile );
                    $content = str_replace( $searchString, $replaceString, $content );
                    file_put_contents( $tempFile, $content );
                }
            }

            // Get the original file's permissions and ownership
            $fileStat = stat( $file );
            $fileMode = $fileStat['mode'];
            $fileUid = $fileStat['uid'];
            $fileGid = $fileStat['gid'];

            // Replace the original file with the temp file
            rename( $tempFile, $file );

            // Restore the original file's permissions and ownership
            chmod( $file, $fileMode );
            chown( $file, $fileUid );
            chgrp( $file, $fileGid );
        }

        /**
         * Convert a title to a valid variable name.
         * @param string $str The title to convert.
         * @return string The converted variable name.
         */
        public function title_to_var_name( $str ) {
            $str = strtolower($str); // Convert all characters to lowercase
            $str = preg_replace('/[^a-z0-9\s]/', '', $str); // Remove all non-alphanumeric characters except spaces
            $str = preg_replace('/\s+/', '_', $str); // Replace one or more spaces with underscores
            $str = preg_replace_callback('/_([a-z])/', function ($match) { return strtoupper($match[1]); }, $str); // Convert underscores to camelCase
            return $str;
        }

        /**
         * Transfer the given job data to a file with admin privileges; allowing a priviledged
         * process to get otherwise inaccessible admin session data.
         * @param string $job_id The unique job id.
         * @param string $key The key of the data to transfer.
         * @return bool True if successful, false otherwise.
         */
        public function xfer_job_data( $job_id, $key ) {
            if ( !isset( $_SESSION['devstia_jobs'][$job_id] ) ) return false;
            if ( !isset( $_SESSION['devstia_jobs'][$job_id][$key] ) ) return false;
            $value = json_encode( $_SESSION['devstia_jobs'][$job_id][$key], JSON_PRETTY_PRINT );
            $file = "/home/admin/tmp/devstia_" . $job_id . "-" . $key . ".json";
            file_put_contents( $file, $value );
            chown( $file, 'admin' );
            chgrp( $file, 'admin' );
            return true;
        }
    }
    new Quickstart();
}
